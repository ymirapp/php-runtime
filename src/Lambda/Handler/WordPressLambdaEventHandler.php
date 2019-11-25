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

use Placeholder\Runtime\Lambda\LambdaInvocationEvent;

/**
 * Lambda invocation event handler for a regular WordPress installation.
 */
class WordPressLambdaEventHandler extends AbstractPhpFpmLambdaEventHandler
{
    /**
     * {@inheritdoc}
     */
    public function canHandle(LambdaInvocationEvent $event): bool
    {
        return file_exists($this->rootDirectory.'/index.php') && file_exists($this->rootDirectory.'/wp-config.php');
    }

    /**
     * {@inheritdoc}
     */
    protected function getScriptFilename(LambdaInvocationEvent $event): string
    {
        $filePath = $this->getEventFilePath($event);

        return false !== stripos($filePath, '.php') ? $filePath : $this->rootDirectory.'/index.php';
    }
}
