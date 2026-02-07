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

namespace Ymir\Runtime\Exception;

use Ymir\Runtime\Lambda\Handler\LambdaEventHandlerInterface;
use Ymir\Runtime\Lambda\InvocationEvent\InvocationEventInterface;

/**
 * Exception thrown when a handler receives an event it cannot handle.
 */
class InvalidHandlerEventException extends \InvalidArgumentException implements ExceptionInterface
{
    /**
     * Constructor.
     */
    public function __construct(LambdaEventHandlerInterface $handler, InvocationEventInterface $event)
    {
        parent::__construct(sprintf('%s cannot handle %s event', (new \ReflectionClass($handler))->getShortName(), (new \ReflectionClass($event))->getShortName()));
    }
}
