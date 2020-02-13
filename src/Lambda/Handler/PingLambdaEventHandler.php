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

use Placeholder\Runtime\Lambda\InvocationEvent\InvocationEventInterface;
use Placeholder\Runtime\Lambda\InvocationEvent\PingEvent;
use Placeholder\Runtime\Lambda\Response\HttpResponse;
use Placeholder\Runtime\Lambda\Response\ResponseInterface;

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
