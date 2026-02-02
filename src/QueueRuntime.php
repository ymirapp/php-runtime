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

namespace Ymir\Runtime;

/**
 * Runtime for "queue" functions.
 */
class QueueRuntime extends AbstractRuntime
{
    /**
     * The function type that the runtime handles.
     */
    public const TYPE = 'queue';
}
