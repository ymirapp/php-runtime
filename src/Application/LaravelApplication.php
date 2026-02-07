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
        $logger = $this->context->getLogger();

        $this->createStorageDirectories($logger);

        $logger->debug('Creating Laravel cache');

        $process = new Process(['/opt/bin/php', $this->context->getRootDirectory().'/artisan', 'config:cache', '--no-ansi']);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ApplicationInitializationException(sprintf('Failed to create Laravel cache: %s', $process->getErrorOutput()));
        }

        $logger->debug('Laravel cache created');
    }
}
