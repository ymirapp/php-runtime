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
        $path = $event->getPath();

        if (1 === preg_match('%^(.+\.php)%i', $path, $matches)) {
            $path = $matches[1];
        }

        if ($this->isMultisite() && (1 === preg_match('/^(.*)?(\/wp-(content|admin|includes).*)/', $path, $matches) || 1 === preg_match('/^(.*)?(\/.*\.php)$/', $path, $matches))) {
            $path = 'wp/'.ltrim($matches[2], '/');
        } elseif ((1 === preg_match('/^\/(wp-.*.php)$/', $path, $matches) || 1 === preg_match('/\/(wp-(content|admin|includes).*)/', $path, $matches))
            && !empty($matches[1])) {
            $path = 'wp/'.ltrim($matches[1], '/');
        }

        $path = ltrim($path, '/');

        if (!str_starts_with($path, 'web/')) {
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
        $application = file_get_contents($this->rootDirectory.'/config/application.php');

        return is_string($application) && 1 === preg_match('/Config::define\(\s*(\'|\")MULTISITE\1\s*,\s*true\s*\)/', $application);
    }
}
