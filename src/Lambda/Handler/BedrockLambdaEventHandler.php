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

namespace Ymir\Runtime\Lambda\Handler;

use Ymir\Runtime\Lambda\InvocationEvent\HttpRequestEvent;
use Ymir\Runtime\Lambda\InvocationEvent\InvocationEventInterface;

/**
 * Lambda invocation event handler for a Bedrock WordPress installation.
 */
class BedrockLambdaEventHandler extends AbstractPhpFpmRequestEventHandler
{
    /**
     * {@inheritdoc}
     */
    public function canHandle(InvocationEventInterface $event): bool
    {
        return parent::canHandle($event)
            && (file_exists($this->rootDirectory.'/web/app/mu-plugins/bedrock-autoloader.php')
                || (is_dir($this->rootDirectory.'/web/app/') && file_exists($this->rootDirectory.'/web/wp-config.php') && file_exists($this->rootDirectory.'/config/application.php')));
    }

    /**
     * {@inheritdoc}
     */
    protected function getEventFilePath(HttpRequestEvent $event): string
    {
        $path = ltrim($event->getPath(), '/');

        if (0 === stripos($path, 'wp/')) {
            $path = 'web/'.$path;
        }

        return $this->rootDirectory.'/'.$path;
    }

    /**
     * {@inheritdoc}
     */
    protected function getScriptFilePath(HttpRequestEvent $event): string
    {
        $filePath = $this->getEventFilePath($event);

        if (is_dir($filePath)) {
            $filePath = rtrim($filePath, '/').'/index.php';
        }

        return file_exists($filePath) ? $filePath : $this->rootDirectory.'/web/index.php';
    }
}
