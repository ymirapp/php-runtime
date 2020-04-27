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
 * A Lambda invocation event from the runtime API.
 */
interface InvocationEventInterface
{
    /**
     * Get the ID of the Lambda invocation.
     */
    public function getId(): string;
}
