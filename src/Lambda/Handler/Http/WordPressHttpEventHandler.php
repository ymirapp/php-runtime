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
use Ymir\Runtime\Lambda\InvocationEvent\InvocationEventInterface;

/**
 * Lambda invocation event handler for a regular WordPress installation.
 */
class WordPressHttpEventHandler extends AbstractPhpFpmRequestEventHandler
{
    /**
     * {@inheritdoc}
     */
    public function canHandle(InvocationEventInterface $event): bool
    {
        return parent::canHandle($event)
            && file_exists($this->rootDirectory.'/index.php')
            && file_exists($this->rootDirectory.'/wp-config.php');
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

        if ($this->isMultisite() && (1 === preg_match('/^(.*)?(\/wp-(content|admin|includes).*)/', $path, $matches) || 1 === preg_match('/^(.*)?(\/.*\.php)/', $path, $matches))) {
            $path = $matches[2];
        }

        return $this->rootDirectory.'/'.ltrim($path, '/');
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

        return file_exists($filePath) ? $filePath : $this->rootDirectory.'/index.php';
    }

    /**
     * {@inheritdoc}
     */
    protected function isPubliclyAccessible(string $filePath): bool
    {
        return 1 !== preg_match('/(wp-config\.php|readme\.html|license\.txt|wp-cli\.local\.yml|wp-cli\.yml)$/', $filePath);
    }

    /**
     * Checks if we're dealing with a multisite installation or not.
     */
    private function isMultisite(): bool
    {
        $wpConfig = file_get_contents($this->rootDirectory.'/wp-config.php');

        return is_string($wpConfig) && 1 === preg_match('/define\(\s*(\'|\")MULTISITE\1\s*,\s*true\s*\)/', $wpConfig);
    }
}
