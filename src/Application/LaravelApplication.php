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

namespace Ymir\Runtime\Application;

use Dotenv\Dotenv;
use Dotenv\Exception\ExceptionInterface as DotenvExceptionInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Ymir\Runtime\Exception\ApplicationInitializationException;
use Ymir\Runtime\Lambda\Handler\Http\LaravelHttpEventHandler;
use Ymir\Runtime\Lambda\Handler\LambdaEventHandlerCollection;
use Ymir\Runtime\Lambda\Handler\Sqs\LaravelSqsHandler;

/**
 * Laravel runtime application.
 */
class LaravelApplication extends AbstractApplication
{
    use CreatesLaravelStorageDirectoriesTrait;

    /**
     * {@inheritDoc}
     */
    public static function present(string $directory): bool
    {
        return file_exists($directory.'/public/index.php')
            && file_exists($directory.'/artisan');
    }

    /**
     * {@inheritDoc}
     */
    public function getQueueHandlers(): LambdaEventHandlerCollection
    {
        return $this->getEventHandlerCollection([
            new LaravelSqsHandler($this->context->getLogger(), $this->context->getRootDirectory()),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getWebsiteHandlers(): LambdaEventHandlerCollection
    {
        return $this->getEventHandlerCollection([
            new LaravelHttpEventHandler($this->context->getLogger(), $this->context->getPhpFpmProcess(), $this->context->getRootDirectory()),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function initialize(): void
    {
        $this->createStorageDirectories();
        $this->decryptEnvironmentFile();
        $this->createConfigurationCache();
    }

    /**
     * Run the given Artisan command.
     */
    protected function runArtisanCommand(array $arguments): void
    {
        $process = new Process(array_merge(['/opt/bin/php', $this->context->getRootDirectory().'/artisan'], $arguments));
        $process->mustRun(null, ['APP_RUNNING_IN_CONSOLE' => 'true']);
    }

    /**
     * Create the Laravel configuration cache.
     */
    private function createConfigurationCache(): void
    {
        $logger = $this->context->getLogger();
        $cacheStart = microtime(true);

        try {
            $this->runArtisanCommand(['config:cache', '--no-ansi']);
        } catch (ProcessFailedException $exception) {
            throw new ApplicationInitializationException($this->getProcessFailureMessage('Failed to create Laravel cache', $exception->getProcess()));
        }

        $logger->debug(sprintf('Laravel cache created in %dms', (microtime(true) - $cacheStart) * 1000));
    }

    /**
     * Decrypt and load the Laravel environment file, if configured.
     */
    private function decryptEnvironmentFile(): void
    {
        $encryptionKey = getenv('LARAVEL_ENV_ENCRYPTION_KEY');

        if (!is_string($encryptionKey) || '' === $encryptionKey) {
            return;
        }

        $environment = $this->getEnvironmentName();
        $encryptedEnvironmentFile = sprintf('%s/.env.%s.encrypted', $this->context->getRootDirectory(), $environment);

        if (!file_exists($encryptedEnvironmentFile)) {
            throw new ApplicationInitializationException(sprintf('Laravel environment encryption key was provided, but encrypted environment file "%s" does not exist', $encryptedEnvironmentFile));
        }

        try {
            $this->runArtisanCommand(['env:decrypt', '--env='.$environment, '--path=/tmp', '--force', '--no-ansi', '--no-interaction']);
        } catch (ProcessFailedException $exception) {
            throw new ApplicationInitializationException($this->getProcessFailureMessage(sprintf('Failed to decrypt Laravel environment file "%s"', $encryptedEnvironmentFile), $exception->getProcess()));
        }

        try {
            Dotenv::createUnsafeMutable('/tmp', '.env.'.$environment)->load();
        } catch (DotenvExceptionInterface $exception) {
            throw new ApplicationInitializationException(sprintf('Failed to load decrypted Laravel environment file "/tmp/.env.%s": %s', $environment, $exception->getMessage()));
        }
    }

    /**
     * Get the Laravel environment name to decrypt.
     */
    private function getEnvironmentName(): string
    {
        $environmentName = getenv('APP_ENV') ?: getenv('YMIR_ENVIRONMENT');

        if (!is_string($environmentName) || '' === $environmentName) {
            throw new ApplicationInitializationException('Unable to determine Laravel environment for encrypted environment file. Set "APP_ENV" or "YMIR_ENVIRONMENT"');
        }

        return $environmentName;
    }

    /**
     * Get the process failure message.
     */
    private function getProcessFailureMessage(string $message, Process $process): string
    {
        $output = trim($process->getErrorOutput()) ?: trim($process->getOutput());

        if (!empty($output)) {
            $message = sprintf('%s: %s', $message, $output);
        }

        return $message;
    }
}
