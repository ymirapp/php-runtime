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
use Placeholder\Runtime\Lambda\LambdaResponse;
use Placeholder\Runtime\Lambda\LambdaResponseInterface;

/**
 * Lambda invocation event handler for pings.
 */
class PingLambdaEventHandler implements LambdaEventHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function canHandle(LambdaInvocationEvent $event): bool
    {
        return $event->isPing();
    }

    /**
     * {@inheritdoc}
     */
    public function handle(LambdaInvocationEvent $event): LambdaResponseInterface
    {
        usleep(50 * 1000);

        return new LambdaResponse('Pong');
    }
}
