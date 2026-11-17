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

namespace Ymir\Runtime\Tests\Unit\Lambda\Handler\Sqs;

use PHPUnit\Framework\TestCase;
use Ymir\Runtime\Lambda\Handler\Sqs\AbstractIlluminateQueueSqsHandler;
use Ymir\Runtime\Lambda\InvocationEvent\SqsEvent;
use Ymir\Runtime\Tests\Mock\InvocationContextMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;

class AbstractIlluminateQueueSqsHandlerTest extends TestCase
{
    use InvocationContextMockTrait;
    use LoggerMockTrait;

    public function testHandlePassesRequestIdToQueueProcess(): void
    {
        $context = $this->getInvocationContextMock();
        $context->method('getRemainingTimeInMs')->willReturn(10000);
        $context->method('getRequestId')->willReturn('lambda-request-id');

        $logger = $this->getLoggerMock();
        $logger->expects($this->once())
               ->method('error')
               ->with($this->stringEndsWith('Test queue job failed: lambda-request-id'));

        $handler = $this->getMockForAbstractClass(AbstractIlluminateQueueSqsHandler::class, [$logger, '/tmp']);
        $handler->method('getProcessArguments')->willReturn(['sh', '-c', 'printf %s "$AWS_REQUEST_ID" >&2; exit 1', 'sh']);
        $handler->method('getQueueName')->willReturn('Test');

        $handler->handle(new SqsEvent($context, ['Records' => [['messageId' => 'id1', 'body' => '{}']]]));
    }
}
