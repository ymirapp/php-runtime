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

namespace Ymir\Runtime\Lambda\InvocationEvent;

/**
 * Lambda invocation event for a console command.
 */
class PhpConsoleCommandEvent extends ConsoleCommandEvent
{
    /**
     * Get the command that the event wants to run.
     */
    public function getCommand(): string
    {
        return sprintf('/opt/bin/php %s', $this->event['php'] ?? '');
    }
}
