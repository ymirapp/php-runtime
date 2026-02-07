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
 * Lambda invocation event handler for a Radicle WordPress installation.
 */
class RadicleHttpEventHandler extends AbstractPhpFpmRequestEventHandler
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

        if ($this->isMultisite() && (1 === preg_match('/^(.*)?(\/wp-(content|admin|includes).*)/', $path, $matches) || 1 === preg_match('/^(.*)?(\/.*\.php)$/', $path, $matches))) {
            $path = 'wp/'.ltrim($matches[2], '/');
        } elseif ('/wp-login.php' !== $path && 1 === preg_match('/^\/(wp-.*.php)$/', $path, $matches) || 1 === preg_match('/\/(wp-(content|admin|includes).*)/', $path, $matches)) {
            $path = 'wp/'.ltrim($matches[1], '/');
        }

        $path = ltrim($path, '/');

        if (!str_starts_with($path, 'public/')) {
            $path = 'public/'.$path;
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

        return file_exists($filePath) ? $filePath : $this->rootDirectory.'/public/index.php';
    }

    /**
     * {@inheritdoc}
     */
    protected function isPubliclyAccessible(string $filePath): bool
    {
        return 1 !== preg_match('/(composer\.(json|lock)|composer\/installed\.json|wp-cli\.local\.yml|wp-cli\.yml)$/', $filePath);
    }

    /**
     * Checks if we're dealing with a multisite installation or not.
     */
    private function isMultisite(): bool
    {
        $application = file_get_contents($this->rootDirectory.'/bedrock/application.php');

        return is_string($application) && 1 === preg_match('/Config::define\(\s*(\'|\")MULTISITE\1\s*,\s*true\s*\)/', $application);
    }
}
