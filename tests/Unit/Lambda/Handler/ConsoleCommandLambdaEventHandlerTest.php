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

namespace Ymir\Runtime\Tests\Unit\Lambda\Handler;

use PHPUnit\Framework\TestCase;
use Ymir\Runtime\Lambda\Handler\ConsoleCommandLambdaEventHandler;
use Ymir\Runtime\Lambda\Response\ProcessResponse;
use Ymir\Runtime\Tests\Mock\ConsoleCommandEventMockTrait;
use Ymir\Runtime\Tests\Mock\InvocationEventInterfaceMockTrait;

/**
 * @covers \Ymir\Runtime\Lambda\Handler\ConsoleCommandLambdaEventHandler
 */
class ConsoleCommandLambdaEventHandlerTest extends TestCase
{
    use ConsoleCommandEventMockTrait;
    use InvocationEventInterfaceMockTrait;

    public function testCanHandlePingEventType()
    {
        $handler = new ConsoleCommandLambdaEventHandler();

        $this->assertTrue($handler->canHandle($this->getConsoleCommandEventMock()));
    }

    public function testCanHandleWrongEventType()
    {
        $handler = new ConsoleCommandLambdaEventHandler();

        $this->assertFalse($handler->canHandle($this->getInvocationEventInterfaceMock()));
    }

    public function testHandleWithSuccessfulCommand()
    {
        $event = $this->getConsoleCommandEventMock();
        $handler = new ConsoleCommandLambdaEventHandler();

        $event->expects($this->once())
              ->method('getCommand')
              ->willReturn('ls -la');

        $response = $handler->handle($event);
        $responseData = $response->getResponseData();

        $this->assertInstanceOf(ProcessResponse::class, $response);
        $this->assertArrayHasKey('exitCode', $responseData);
        $this->assertSame(0, $responseData['exitCode']);
    }

    public function testHandleWithUnsuccessfulCommand()
    {
        $event = $this->getConsoleCommandEventMock();
        $handler = new ConsoleCommandLambdaEventHandler();

        $event->expects($this->once())
              ->method('getCommand')
              ->willReturn('foo');

        $response = $handler->handle($event);
        $responseData = $response->getResponseData();

        $this->assertInstanceOf(ProcessResponse::class, $response);
        $this->assertArrayHasKey('exitCode', $responseData);
        $this->assertNotSame(0, $responseData['exitCode']);
    }

    public function testHandleWithWrongEventType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ConsoleCommandLambdaEventHandler can only handle ConsoleCommandEvent objects');

        $handler = new ConsoleCommandLambdaEventHandler();

        $handler->handle($this->getInvocationEventInterfaceMock());
    }
}
