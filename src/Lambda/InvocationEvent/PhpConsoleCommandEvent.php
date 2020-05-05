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
     * Constructor.
     */
    public function __construct(string $id, string $command)
    {
        parent::__construct($id, sprintf('/opt/bin/php %s', $command));
    }
}
