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

namespace Ymir\Runtime\Lambda\Handler\Http;

use Ymir\Runtime\Lambda\InvocationEvent\HttpRequestEvent;

/**
 * Lambda invocation event handler for a Laravel application.
 */
class LaravelHttpEventHandler extends AbstractPhpFpmRequestEventHandler
{
    /**
     * {@inheritdoc}
     */
    protected function getEventFilePath(HttpRequestEvent $event): string
    {
        $path = $event->getPath();

        if (1 === preg_match('%^(.+\.php)%i', $path, $matches)) {
            $path = $matches[1];
        }

        $filePath = $this->rootDirectory.'/public/'.ltrim($path, '/');

        if (!file_exists($filePath) && str_starts_with($path, '/storage/')) {
            $filePath = $this->rootDirectory.'/storage/app/public/'.substr($path, 9);
        }

        return $filePath;
    }

    /**
     * {@inheritdoc}
     */
    protected function getScriptFilePath(HttpRequestEvent $event): string
    {
        $filePath = $this->getEventFilePath($event);

        return file_exists($filePath) && 1 === preg_match('/\.php$/i', $filePath) ? $filePath : $this->rootDirectory.'/public/index.php';
    }

    /**
     * {@inheritdoc}
     */
    protected function isPubliclyAccessible(string $filePath): bool
    {
        return 1 !== preg_match('/(composer\.(json|lock)|composer\/installed\.json|package(-lock)?\.json|yarn\.lock)$/', $filePath);
    }
}
