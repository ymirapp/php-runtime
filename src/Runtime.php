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
use Ymir\Runtime\Application\ApplicationFactory;
use Ymir\Runtime\Aws\SsmClient;
use Ymir\Runtime\Exception\InvalidConfigurationException;

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
        $runtimeApiClient = $context->getRuntimeApiClient();

        try {
            $functionType = getenv('YMIR_FUNCTION_TYPE');

            if (!is_string($functionType)) {
                throw new InvalidConfigurationException('The "YMIR_FUNCTION_TYPE" environment variable is missing');
            }

            self::injectSecretEnvironmentVariables($context);

            $application = ApplicationFactory::createFromContext($context);
            $application->initialize();

            switch ($functionType) {
                case ConsoleRuntime::TYPE:
                    $runtime = ConsoleRuntime::createFromApplication($application);

                    break;
                case QueueRuntime::TYPE:
                    $runtime = QueueRuntime::createFromApplication($application);

                    break;
                case WebsiteRuntime::TYPE:
                    $runtime = WebsiteRuntime::createFromApplication($application);

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
