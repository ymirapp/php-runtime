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

use Ymir\Runtime\FastCgi\PhpFpmProcess;
use Ymir\Runtime\Lambda\Handler\BedrockLambdaEventHandler;
use Ymir\Runtime\Lambda\Handler\ConsoleCommandLambdaEventHandler;
use Ymir\Runtime\Lambda\Handler\LambdaEventHandlerCollection;
use Ymir\Runtime\Lambda\Handler\LambdaEventHandlerInterface;
use Ymir\Runtime\Lambda\Handler\PhpScriptLambdaEventHandler;
use Ymir\Runtime\Lambda\Handler\PingLambdaEventHandler;
use Ymir\Runtime\Lambda\Handler\WordPressLambdaEventHandler;
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
        $phpFpmProcess = PhpFpmProcess::createForConfig($logger);
        $rootDirectory = getenv('LAMBDA_TASK_ROOT');

        if (!is_string($apiUrl)) {
            throw new \Exception('The "AWS_LAMBDA_RUNTIME_API" environment variable is missing');
        } elseif (!is_string($rootDirectory)) {
            throw new \Exception('The "LAMBDA_TASK_ROOT" environment variable is missing');
        }

        return new self(
            new RuntimeApiClient($apiUrl, $logger),
            new LambdaEventHandlerCollection($logger, [
                new PingLambdaEventHandler(),
                new ConsoleCommandLambdaEventHandler(),
                new WordPressLambdaEventHandler($phpFpmProcess, $rootDirectory),
                new BedrockLambdaEventHandler($phpFpmProcess, $rootDirectory),
                new PhpScriptLambdaEventHandler($phpFpmProcess, $rootDirectory, getenv('_HANDLER') ?: 'index.php'),
            ]),
            $logger,
            $phpFpmProcess
        );
    }

    /**
     * Process the next Lambda runtime API event.
     */
    public function processNextEvent()
    {
        $event = $this->client->getNextEvent();

        try {
            if (!$this->handler->canHandle($event)) {
                throw new \Exception('Unable to handle the given event');
            }

            $this->client->sendResponse($event, $this->handler->handle($event));

            ++$this->invocations;
        } catch (\Throwable $exception) {
            $this->logger->exception($exception);
            $this->client->sendEventError($event, $exception);
        }

        if ($this->invocations >= $this->maxInvocations) {
            $this->logger->info(sprintf('Killing Lambda container. Container has processed %s invocation events. (%s)', $this->maxInvocations, $event->getId()));
            exit(0);
        }
    }

    /**
     * Start the Lambda runtime.
     */
    public function start()
    {
        try {
            $this->phpFpmProcess->start();
        } catch (\Throwable $exception) {
            $this->logger->exception($exception);
            $this->client->sendInitializationError($exception);

            exit(1);
        }
    }
}
