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
use Symfony\Component\Process\Exception\ProcessFailedException;
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

        $this->assertInstanceOf(ProcessResponse::class, $handler->handle($event));
    }

    public function testHandleWithUnsuccessfulCommand()
    {
        $this->expectException(ProcessFailedException::class);

        $event = $this->getConsoleCommandEventMock();
        $handler = new ConsoleCommandLambdaEventHandler();

        $event->expects($this->once())
              ->method('getCommand')
              ->willReturn('foo');

        $handler->handle($event);
    }

    public function testHandleWithWrongEventType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ConsoleCommandLambdaEventHandler can only handle ConsoleCommandEvent objects');

        $handler = new ConsoleCommandLambdaEventHandler();

        $handler->handle($this->getInvocationEventInterfaceMock());
    }
}
