<?php

declare(strict_types=1);

/*
 * This file is part of Ymir PHP Runtime.
 *
 * (c) Carl Alexander <support@ymirapp.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ymir\Runtime;

use AsyncAws\Lambda\LambdaClient;
use AsyncAws\Ssm\Input\GetParametersByPathRequest;
use AsyncAws\Ssm\SsmClient;
use AsyncAws\Ssm\ValueObject\Parameter;
use hollodotme\FastCGI\Exceptions\ReadFailedException;
use Tightenco\Collect\Support\Arr;
use Ymir\Runtime\FastCgi\PhpFpmProcess;
use Ymir\Runtime\Lambda\Handler;
use Ymir\Runtime\Lambda\Handler\LambdaEventHandlerInterface;
use Ymir\Runtime\Lambda\Response\BadGatewayHttpResponse;
use Ymir\Runtime\Lambda\RuntimeApiClient;

/**
 * The Ymir PHP runtime.
 */
class Runtime
{
    /**
     * The Lambda runtime API client.
     *
     * @var RuntimeApiClient
     */
    private $client;

    /**
     * The Lambda invocation event handler used by the runtime.
     *
     * @var LambdaEventHandlerInterface
     */
    private $handler;

    /**
     * The current number of invocations.
     *
     * @var int
     */
    private $invocations;

    /**
     * The logger that sends logs to CloudWatch.
     *
     * @var Logger
     */
    private $logger;

    /**
     * The maximum number of invocations.
     *
     * @var int
     */
    private $maxInvocations;

    /**
     * The PHP-FPM process used by the runtime.
     *
     * @var PhpFpmProcess
     */
    private $phpFpmProcess;

    /**
     * Constructor.
     */
    public function __construct(RuntimeApiClient $client, LambdaEventHandlerInterface $handler, Logger $logger, PhpFpmProcess $phpFpmProcess, int $maxInvocations = 100)
    {
        if (0 >= $maxInvocations) {
            throw new \InvalidArgumentException('"maxInvocations" must be greater than 0');
        }

        $this->client = $client;
        $this->handler = $handler;
        $this->invocations = 0;
        $this->logger = $logger;
        $this->maxInvocations = $maxInvocations;
        $this->phpFpmProcess = $phpFpmProcess;
    }

    /**
     * Create new runtime from the Lambda environment variable.
     */
    public static function createFromEnvironmentVariable(): self
    {
        $apiUrl = getenv('AWS_LAMBDA_RUNTIME_API');
        $logger = new Logger(getenv('YMIR_RUNTIME_LOG_LEVEL') ?: Logger::INFO);
        $maxInvocations = getenv('YMIR_RUNTIME_MAX_INVOCATIONS') ?: 100;
        $phpFpmProcess = PhpFpmProcess::createForConfig($logger);
        $region = getenv('AWS_REGION');
        $rootDirectory = getenv('LAMBDA_TASK_ROOT');

        if (!is_string($apiUrl)) {
            throw new \Exception('The "AWS_LAMBDA_RUNTIME_API" environment variable is missing');
        } elseif (!is_string($rootDirectory)) {
            throw new \Exception('The "LAMBDA_TASK_ROOT" environment variable is missing');
        } elseif (!is_string($region)) {
            throw new \Exception('The "AWS_REGION" environment variable is missing');
        }

        self::injectSecretEnvironmentVariables($logger, $region);

        return new self(
            new RuntimeApiClient($apiUrl, $logger),
            new Handler\LambdaEventHandlerCollection($logger, [
                // Internal handlers
                new Handler\PingLambdaEventHandler(),
                new Handler\WarmUpEventHandler(new LambdaClient(['region' => $region], null, null, $logger)),

                // Specialized event type handlers
                new Handler\ConsoleCommandLambdaEventHandler($logger),

                // Application/Framework specific handlers
                new Handler\WordPressLambdaEventHandler($logger, $phpFpmProcess, $rootDirectory),
                new Handler\BedrockLambdaEventHandler($logger, $phpFpmProcess, $rootDirectory),
                new Handler\RadicleLambdaEventHandler($logger, $phpFpmProcess, $rootDirectory),
                new Handler\LaravelLambdaEventHandler($logger, $phpFpmProcess, $rootDirectory),

                // Fallback handlers
                new Handler\PhpScriptLambdaEventHandler($logger, $phpFpmProcess, $rootDirectory, getenv('_HANDLER') ?: 'index.php'),
            ]),
            $logger,
            $phpFpmProcess,
            (int) $maxInvocations
        );
    }

    /**
     * Inject the secret environment variables into the runtime.
     */
    private static function injectSecretEnvironmentVariables(Logger $logger, string $region): void
    {
        $secretsPath = getenv('YMIR_SECRETS_PATH');

        if (!is_string($secretsPath)) {
            return;
        }

        // Need to pass results through iterator_to_array manually because the collection object
        // preserves keys. This causes the next page of results to overwrite the previous page of
        // results because they use a numbered index.
        //
        // @see https://stackoverflow.com/questions/70536304/why-does-iterator-to-array-give-different-results-than-foreach
        collect(iterator_to_array((new SsmClient(['region' => $region], null, null, $logger))->getParametersByPath(new GetParametersByPathRequest([
            'Path' => $secretsPath,
            'WithDecryption' => true,
        ])), false))->mapWithKeys(function (Parameter $parameter) {
            return [Arr::last(explode('/', (string) $parameter->getName())) => (string) $parameter->getValue()];
        })->filter()->each(function ($value, $name) use ($logger) {
            $logger->debug(sprintf('Injecting [%s] secret environment variable into runtime', $name));
            $_ENV[$name] = $value;
        });
    }

    /**
     * Process the next Lambda runtime API event.
     */
    public function processNextEvent(): void
    {
        $event = $this->client->getNextEvent();

        try {
            if (!$this->handler->canHandle($event)) {
                throw new \Exception('Unable to handle the given event');
            }

            $this->client->sendResponse($event, $this->handler->handle($event));

            ++$this->invocations;
        } catch (ReadFailedException $exception) {
            $this->logger->exception($exception);

            $this->client->sendResponse($event, new BadGatewayHttpResponse('The process handling the request crashed unexpectedly'));

            $this->logger->info('Killing Lambda container. PHP-FPM process has crashed.');
            $this->terminate(1);
        } catch (\Throwable $exception) {
            $this->logger->exception($exception);
            $this->client->sendEventError($event, $exception);
        }

        if ($this->invocations >= $this->maxInvocations) {
            $this->logger->info(sprintf('Killing Lambda container. Container has processed %s invocation events. (%s)', $this->maxInvocations, $event->getId()));
            $this->terminate(0);
        }
    }

    /**
     * Start the Lambda runtime.
     */
    public function start(): void
    {
        try {
            $this->phpFpmProcess->start();
        } catch (\Throwable $exception) {
            $this->logger->exception($exception);
            $this->client->sendInitializationError($exception);

            $this->terminate(1);
        }
    }

    /**
     * Terminate the runtime.
     */
    protected function terminate(int $code): void
    {
        exit($code);
    }
}
