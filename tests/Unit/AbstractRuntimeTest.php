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

namespace Ymir\Runtime\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ymir\Runtime\AbstractRuntime;
use Ymir\Runtime\Tests\Mock\InvocationContextMockTrait;
use Ymir\Runtime\Tests\Mock\InvocationEventInterfaceMockTrait;
use Ymir\Runtime\Tests\Mock\LambdaEventHandlerInterfaceMockTrait;
use Ymir\Runtime\Tests\Mock\LambdaRuntimeApiClientMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;
use Ymir\Runtime\Tests\Mock\ResponseInterfaceMockTrait;

class AbstractRuntimeTest extends TestCase
{
    use InvocationContextMockTrait;
    use InvocationEventInterfaceMockTrait;
    use LambdaEventHandlerInterfaceMockTrait;
    use LambdaRuntimeApiClientMockTrait;
    use LoggerMockTrait;
    use ResponseInterfaceMockTrait;

    public function testProcessNextEvent(): void
    {
        $client = $this->getLambdaRuntimeApiClientMock();
        $event = $this->getInvocationEventInterfaceMock();
        $handler = $this->getLambdaEventHandlerInterfaceMock();
        $logger = $this->getLoggerMock();
        $response = $this->getResponseInterfaceMock();

        $runtime = $this->getMockForAbstractClass(AbstractRuntime::class, [$client, $handler, $logger]);

        $client->expects($this->once())
               ->method('getNextEvent')
               ->willReturn($event);
        $client->expects($this->once())
                ->method('sendResponse')
                ->with($this->identicalTo($event), $this->identicalTo($response));

        $handler->expects($this->once())
                 ->method('canHandle')
                 ->with($this->identicalTo($event))
                 ->willReturn(true);
        $handler->expects($this->once())
                 ->method('handle')
                 ->with($this->identicalTo($event))
                 ->willReturn($response);

        $runtime->processNextEvent();
    }

    public function testProcessNextEventWithException(): void
    {
        $client = $this->getLambdaRuntimeApiClientMock();
        $context = $this->getInvocationContextMock();
        $event = $this->getInvocationEventInterfaceMock();
        $exception = new \Exception('test exception');
        $handler = $this->getLambdaEventHandlerInterfaceMock();
        $logger = $this->getLoggerMock();

        $runtime = $this->getMockForAbstractClass(AbstractRuntime::class, [$client, $handler, $logger]);

        $event->method('getContext')->willReturn($context);

        $client->expects($this->once())
               ->method('getNextEvent')
               ->willReturn($event);
        $client->expects($this->once())
                ->method('sendError')
                ->with($this->identicalTo($context), $this->identicalTo($exception));

        $handler->expects($this->once())
                 ->method('canHandle')
                 ->with($this->identicalTo($event))
                 ->willReturn(true);
        $handler->expects($this->once())
                 ->method('handle')
                 ->with($this->identicalTo($event))
                 ->willThrowException($exception);

        $logger->expects($this->once())
               ->method('exception')
               ->with($this->identicalTo($exception));

        $runtime->processNextEvent();
    }

    public function testProcessNextEventWithUnhandledEvent(): void
    {
        $client = $this->getLambdaRuntimeApiClientMock();
        $context = $this->getInvocationContextMock();
        $event = $this->getInvocationEventInterfaceMock();
        $handler = $this->getLambdaEventHandlerInterfaceMock();
        $logger = $this->getLoggerMock();

        $runtime = $this->getMockForAbstractClass(AbstractRuntime::class, [$client, $handler, $logger]);

        $event->method('getContext')->willReturn($context);

        $client->expects($this->once())
               ->method('getNextEvent')
               ->willReturn($event);
        $client->expects($this->once())
                ->method('sendError')
                ->with($this->identicalTo($context), $this->isInstanceOf(\Exception::class));

        $handler->expects($this->once())
                 ->method('canHandle')
                 ->with($this->identicalTo($event))
                 ->willReturn(false);

        $logger->expects($this->once())
               ->method('exception')
               ->with($this->callback(function ($exception) {
                   return $exception instanceof \Exception && 'Unable to handle the given event' === $exception->getMessage();
               }));

        $runtime->processNextEvent();
    }
}
