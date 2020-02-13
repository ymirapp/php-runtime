<?php

declare(strict_types=1);

/*
 * This file is part of Placeholder PHP Runtime.
 *
 * (c) Carl Alexander <contact@carlalexander.ca>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Placeholder\Runtime\Lambda\Handler;

use Placeholder\Runtime\Lambda\InvocationEvent\HttpRequestEvent;
use Placeholder\Runtime\Lambda\InvocationEvent\InvocationEventInterface;

/**
 * Lambda invocation event handler for a regular WordPress installation.
 */
class WordPressLambdaEventHandler extends AbstractPhpFpmRequestEventHandler
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
    protected function getScriptFilePath(HttpRequestEvent $event): string
    {
        $filePath = $this->getEventFilePath($event);

        if (false === stripos($filePath, '.php')) {
            $filePath .= 'index.php';
        }

        if (file_exists($filePath)) {
            return $filePath;
        }

        return $this->rootDirectory.'/index.php';
    }
}
