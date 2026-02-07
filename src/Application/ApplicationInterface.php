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

use Ymir\Runtime\Lambda\Handler\LambdaEventHandlerCollection;
use Ymir\Runtime\RuntimeContext;

/**
 * An application executed by the runtime.
 */
interface ApplicationInterface
{
    /**
     * Check if the application is present in the given directory.
     */
    public static function present(string $directory): bool;

    /**
     * Get the application handlers for "console" function.
     */
    public function getConsoleHandlers(): LambdaEventHandlerCollection;

    /**
     * Get the application Lambda runtime context.
     */
    public function getContext(): RuntimeContext;

    /**
     * Get the application handlers for "queue" function.
     */
    public function getQueueHandlers(): LambdaEventHandlerCollection;

    /**
     * Get the application handlers for "website" function.
     */
    public function getWebsiteHandlers(): LambdaEventHandlerCollection;

    /**
     * Initialize the application.
     */
    public function initialize(): void;
}
