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
use Ymir\Runtime\Tests\Mock\ContextMockTrait;
use Ymir\Runtime\Tests\Mock\InvocationEventInterfaceMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;

class ConsoleCommandLambdaEventHandlerTest extends TestCase
{
    use ConsoleCommandEventMockTrait;
    use ContextMockTrait;
    use InvocationEventInterfaceMockTrait;
    use LoggerMockTrait;

    public static function provideTimeouts(): \Iterator
    {
        yield [60000, 59.0];
        yield [10000, 9.0];
        yield [2000, 1.0];
        yield [1000, 1.0];
        yield [500, 1.0];
    }

    public function testCanHandlePingEventType(): void
    {
        $this->assertTrue((new ConsoleCommandLambdaEventHandler($this->getLoggerMock()))->canHandle($this->getConsoleCommandEventMock()));
    }

    public function testCanHandleWrongEventType(): void
    {
        $this->assertFalse((new ConsoleCommandLambdaEventHandler($this->getLoggerMock()))->canHandle($this->getInvocationEventInterfaceMock()));
    }

    /**
     * @dataProvider provideTimeouts
     */
    public function testHandleSetsCorrectTimeout(int $remainingTimeInMs, float $expectedTimeout): void
    {
        $context = $this->getContextMock();
        $event = $this->getConsoleCommandEventMock();
        $logger = $this->getLoggerMock();

        $event->expects($this->once())
              ->method('getCommand')
              ->willReturn('ls -la');

        $event->expects($this->once())
              ->method('getContext')
              ->willReturn($context);

        $context->expects($this->once())
                ->method('getRemainingTimeInMs')
                ->willReturn($remainingTimeInMs);

        $response = (new ConsoleCommandLambdaEventHandler($logger))->handle($event);

        $reflection = new \ReflectionClass(ProcessResponse::class);
        $property = $reflection->getProperty('process');
        $property->setAccessible(true);

        $process = $property->getValue($response);

        $this->assertEquals($expectedTimeout, $process->getTimeout());
    }

    public function testHandleWithSuccessfulCommand(): void
    {
        $context = $this->getContextMock();
        $event = $this->getConsoleCommandEventMock();
        $logger = $this->getLoggerMock();

        $event->expects($this->once())
              ->method('getCommand')
              ->willReturn('ls -la');

        $event->expects($this->once())
              ->method('getContext')
              ->willReturn($context);

        $context->expects($this->once())
                ->method('getRemainingTimeInMs')
                ->willReturn(60000);

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
        $context = $this->getContextMock();
        $event = $this->getConsoleCommandEventMock();
        $logger = $this->getLoggerMock();

        $event->expects($this->once())
              ->method('getCommand')
              ->willReturn('foo');

        $event->expects($this->once())
              ->method('getContext')
              ->willReturn($context);

        $context->expects($this->once())
                ->method('getRemainingTimeInMs')
                ->willReturn(60000);

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
