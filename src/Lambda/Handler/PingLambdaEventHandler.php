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

namespace Ymir\Runtime\Lambda\Handler;

use Ymir\Runtime\Lambda\InvocationEvent\InvocationEventInterface;
use Ymir\Runtime\Lambda\InvocationEvent\PingEvent;
use Ymir\Runtime\Lambda\Response\HttpResponse;
use Ymir\Runtime\Lambda\Response\ResponseInterface;

/**
 * Lambda invocation event handler for pings.
 */
class PingLambdaEventHandler implements LambdaEventHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function canHandle(InvocationEventInterface $event): bool
    {
        return $event instanceof PingEvent;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(InvocationEventInterface $event): ResponseInterface
    {
        usleep(50 * 1000);

        return new HttpResponse('Pong');
    }
}
