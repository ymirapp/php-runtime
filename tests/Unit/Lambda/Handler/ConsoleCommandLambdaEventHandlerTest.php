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
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;

/**
 * @covers \Ymir\Runtime\Lambda\Handler\ConsoleCommandLambdaEventHandler
 */
class ConsoleCommandLambdaEventHandlerTest extends TestCase
{
    use ConsoleCommandEventMockTrait;
    use InvocationEventInterfaceMockTrait;
    use LoggerMockTrait;

    public function testCanHandlePingEventType(): void
    {
        $this->assertTrue((new ConsoleCommandLambdaEventHandler($this->getLoggerMock()))->canHandle($this->getConsoleCommandEventMock()));
    }

    public function testCanHandleWrongEventType(): void
    {
        $this->assertFalse((new ConsoleCommandLambdaEventHandler($this->getLoggerMock()))->canHandle($this->getInvocationEventInterfaceMock()));
    }

    public function testHandleWithSuccessfulCommand(): void
    {
        $event = $this->getConsoleCommandEventMock();
        $logger = $this->getLoggerMock();

        $event->expects($this->once())
              ->method('getCommand')
              ->willReturn('ls -la');

        $logger->expects($this->once())
               ->method('info');

        $response = (new ConsoleCommandLambdaEventHandler($logger))->handle($event);
        $responseData = $response->getResponseData();

        $this->assertInstanceOf(ProcessResponse::class, $response);
        $this->assertArrayHasKey('exitCode', $responseData);
        $this->assertSame(0, $responseData['exitCode']);
    }

    public function testHandleWithUnsuccessfulCommand(): void
    {
        $event = $this->getConsoleCommandEventMock();
        $logger = $this->getLoggerMock();

        $event->expects($this->once())
              ->method('getCommand')
              ->willReturn('foo');

        $logger->expects($this->once())
               ->method('info');

        $response = (new ConsoleCommandLambdaEventHandler($logger))->handle($event);
        $responseData = $response->getResponseData();

        $this->assertInstanceOf(ProcessResponse::class, $response);
        $this->assertArrayHasKey('exitCode', $responseData);
        $this->assertNotSame(0, $responseData['exitCode']);
    }

    public function testHandleWithWrongEventType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ConsoleCommandLambdaEventHandler can only handle ConsoleCommandEvent objects');

        (new ConsoleCommandLambdaEventHandler($this->getLoggerMock()))->handle($this->getInvocationEventInterfaceMock());
    }
}
