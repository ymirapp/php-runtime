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
use Ymir\Runtime\Lambda\Handler\Http\RadicleHttpEventHandler;
use Ymir\Runtime\Lambda\Handler\LambdaEventHandlerCollection;

/**
 * WordPress Radicle runtime application.
 */
class RadicleApplication extends AbstractApplication
{
    use CreatesLaravelStorageDirectoriesTrait;

    /**
     * {@inheritDoc}
     */
    public static function present(string $directory): bool
    {
        return file_exists($directory.'/public/content/mu-plugins/bedrock-autoloader.php')
            || (is_dir($directory.'/public/') && file_exists($directory.'/public/wp-config.php') && file_exists($directory.'/bedrock/application.php'));
    }

    /**
     * {@inheritDoc}
     */
    public function getWebsiteHandlers(): LambdaEventHandlerCollection
    {
        return $this->getEventHandlerCollection([
            new RadicleHttpEventHandler($this->context->getLogger(), $this->context->getPhpFpmProcess(), $this->context->getRootDirectory()),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function initialize(): void
    {
        $logger = $this->context->getLogger();

        $this->createStorageDirectories($logger);

        $logger->debug('Creating Acorn cache');

        $process = new Process(['/opt/bin/php', $this->context->getRootDirectory().'/bin/wp', 'acorn', 'config:cache', '--no-ansi']);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ApplicationInitializationException(sprintf('Failed to create Acorn cache: %s', $process->getErrorOutput()));
        }

        $logger->debug('Acorn cache created');
    }
}
