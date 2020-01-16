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
use Placeholder\Runtime\Lambda\LambdaResponseInterface;

/**
 * Event handlers take a Lambda invocation event and convert it to a response
 * to send back to the Lambda runtime API.
 */
interface LambdaEventHandlerInterface
{
    /**
     * Can the handler handle the given Lambda invocation event.
     */
    public function canHandle(LambdaInvocationEvent $event): bool;

    /**
     * Handles the given Lambda invocation event and returns the response
     * to send back to the Lambda runtime API.
     */
    public function handle(LambdaInvocationEvent $event): LambdaResponseInterface;
}
