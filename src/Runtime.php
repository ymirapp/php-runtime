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

use AsyncAws\Ssm\Input\GetParametersByPathRequest;
use AsyncAws\Ssm\ValueObject\Parameter;
use Tightenco\Collect\Support\Arr;
use Ymir\Runtime\Aws\LambdaClient;
use Ymir\Runtime\Aws\SsmClient;
use Ymir\Runtime\Exception\InvalidConfigurationException;
use Ymir\Runtime\Lambda\Handler\ConsoleCommandLambdaEventHandler;
use Ymir\Runtime\Lambda\Handler\Http as HttpHandler;
use Ymir\Runtime\Lambda\Handler\LambdaEventHandlerCollection;
use Ymir\Runtime\Lambda\Handler\PingLambdaEventHandler;
use Ymir\Runtime\Lambda\Handler\Sqs;
use Ymir\Runtime\Lambda\Handler\WarmUpEventHandler;

/**
 * Ymir Runtime factory.
 */
class Runtime
{
    /**
     * Create new runtime from the Lambda environment variable.
     */
    public static function create(): RuntimeInterface
    {
        $coldStart = microtime(true);
        $context = RuntimeContext::createFromEnvironment();

        $logger = $context->getLogger();
        $rootDirectory = $context->getRootDirectory();
        $runtimeApiClient = $context->getRuntimeApiClient();

        try {
            $functionType = getenv('YMIR_FUNCTION_TYPE');

            if (!is_string($functionType)) {
                throw new InvalidConfigurationException('The "YMIR_FUNCTION_TYPE" environment variable is missing');
            }

            self::injectSecretEnvironmentVariables($context);

            $handlers = [
                new PingLambdaEventHandler(),
                new WarmUpEventHandler(LambdaClient::createFromContext($context), $logger),
            ];

            switch ($functionType) {
                case ConsoleRuntime::TYPE:
                    $runtime = new ConsoleRuntime($runtimeApiClient, new LambdaEventHandlerCollection($logger, array_merge($handlers, [
                        new ConsoleCommandLambdaEventHandler($logger),
                    ])), $logger);

                    break;
                case QueueRuntime::TYPE:
                    $runtime = new QueueRuntime($runtimeApiClient, new LambdaEventHandlerCollection($logger, array_merge($handlers, [
                        // Application/Framework specific handlers
                        new Sqs\LaravelSqsHandler($logger, $rootDirectory),
                    ])), $logger);

                    break;
                case WebsiteRuntime::TYPE:
                    $phpFpmProcess = $context->getPhpFpmProcess();

                    $runtime = new WebsiteRuntime($runtimeApiClient, new LambdaEventHandlerCollection($logger, array_merge($handlers, [
                        // Application/Framework specific handlers
                        new HttpHandler\WordPressHttpEventHandler($logger, $phpFpmProcess, $rootDirectory),
                        new HttpHandler\BedrockHttpEventHandler($logger, $phpFpmProcess, $rootDirectory),
                        new HttpHandler\RadicleHttpEventHandler($logger, $phpFpmProcess, $rootDirectory),
                        new HttpHandler\LaravelHttpEventHandler($logger, $phpFpmProcess, $rootDirectory),

                        // Fallback handlers
                        new HttpHandler\PhpScriptHttpEventHandler($logger, $phpFpmProcess, $rootDirectory, getenv('_HANDLER') ?: 'index.php'),
                    ])), $logger, $phpFpmProcess, $context->getMaxInvocations());

                    $runtime->start();

                    break;
                default:
                    throw new InvalidConfigurationException(sprintf('Unknown function type: "%s"', $functionType));
            }

            $logger->info(sprintf('Ymir PHP Runtime (%s) initialized in %dms', $functionType, (microtime(true) - $coldStart) * 1000));

            return $runtime;
        } catch (\Throwable $exception) {
            $logger->exception($exception);
            $runtimeApiClient->sendInitializationError($exception);

            exit(1);
        }
    }

    /**
     * Inject the secret environment variables into the runtime.
     */
    private static function injectSecretEnvironmentVariables(RuntimeContext $context): void
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
        collect(iterator_to_array(SsmClient::createFromContext($context)->getParametersByPath(new GetParametersByPathRequest([
            'Path' => $secretsPath,
            'WithDecryption' => true,
        ])), false))->mapWithKeys(function (Parameter $parameter) {
            return [Arr::last(explode('/', (string) $parameter->getName())) => (string) $parameter->getValue()];
        })->filter()->each(function ($value, $name) use ($context): void {
            $context->getLogger()->debug(sprintf('Injecting [%s] secret environment variable into runtime', $name));
            $_ENV[$name] = $value;
        });
    }
}
