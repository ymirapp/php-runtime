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
use Ymir\Runtime\Lambda\Response\ResponseInterface;

/**
 * Event handlers take a Lambda invocation event and convert it to a response
 * to send back to the Lambda runtime API.
 */
interface LambdaEventHandlerInterface
{
    /**
     * Can the handler handle the given Lambda invocation event.
     */
    public function canHandle(InvocationEventInterface $event): bool;

    /**
     * Handles the given Lambda invocation event and returns the response
     * to send back to the Lambda runtime API.
     */
    public function handle(InvocationEventInterface $event): ResponseInterface;
}
