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

namespace Placeholder\Runtime\Lambda\InvocationEvent;

/**
 * Lambda invocation event for a console command.
 */
class ConsoleCommandEvent extends AbstractEvent
{
    /**
     * Get the command that the event wants to run.
     */
    public function getCommand(): string
    {
        return $this->event['command'] ?? '';
    }
}
