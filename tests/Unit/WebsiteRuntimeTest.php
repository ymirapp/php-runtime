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

use hollodotme\FastCGI\Exceptions\ReadFailedException;
use PHPUnit\Framework\TestCase;
use Ymir\Runtime\Lambda\Response\BadGatewayHttpResponse;
use Ymir\Runtime\Tests\Mock\InvocationEventInterfaceMockTrait;
use Ymir\Runtime\Tests\Mock\LambdaEventHandlerInterfaceMockTrait;
use Ymir\Runtime\Tests\Mock\LambdaRuntimeApiClientMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;
use Ymir\Runtime\Tests\Mock\PhpFpmProcessMockTrait;
use Ymir\Runtime\Tests\Mock\ResponseInterfaceMockTrait;
use Ymir\Runtime\WebsiteRuntime;

/**
 * @covers \Ymir\Runtime\WebsiteRuntime
 */
class WebsiteRuntimeTest extends TestCase
{
    use InvocationEventInterfaceMockTrait;
    use LambdaEventHandlerInterfaceMockTrait;
    use LambdaRuntimeApiClientMockTrait;
    use LoggerMockTrait;
    use PhpFpmProcessMockTrait;
    use ResponseInterfaceMockTrait;

    public function testConstructorWithMaxInvocationLessThan1(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"maxInvocations" must be greater than 0');

        $client = $this->getLambdaRuntimeApiClientMock();
        $handler = $this->getLambdaEventHandlerInterfaceMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        new WebsiteRuntime($client, $handler, $logger, $process, 0);
    }

    public function testProcessNextEventWithMaxInvocationsReached(): void
    {
        $client = $this->getLambdaRuntimeApiClientMock();
        $event = $this->getInvocationEventInterfaceMock();
        $handler = $this->getLambdaEventHandlerInterfaceMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();
        $response = $this->getResponseInterfaceMock();

        $runtime = $this->getMockBuilder(WebsiteRuntime::class)
                        ->setConstructorArgs([$client, $handler, $logger, $process, 1])
                        ->setMethods(['terminate'])
                        ->getMock();

        $client->expects($this->once())
               ->method('getNextEvent')
               ->willReturn($event);
        $client->expects($this->once())
                ->method('sendResponse')
                ->with($this->identicalTo($event), $this->identicalTo($response));

        $event->expects($this->once())
              ->method('getId')
              ->willReturn('test-id');

        $handler->expects($this->once())
                ->method('canHandle')
                ->with($this->identicalTo($event))
                ->willReturn(true);
        $handler->expects($this->once())
                ->method('handle')
                ->with($this->identicalTo($event))
                ->willReturn($response);

        $logger->expects($this->once())
               ->method('info')
               ->with('Killing Lambda container. Container has processed 1 invocation events. (test-id)');

        $runtime->expects($this->once())
                 ->method('terminate')
                 ->with(0);

        $runtime->processNextEvent();
    }

    public function testProcessNextEventWithReadFailedException(): void
    {
        $client = $this->getLambdaRuntimeApiClientMock();
        $event = $this->getInvocationEventInterfaceMock();
        $handler = $this->getLambdaEventHandlerInterfaceMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $runtime = $this->getMockBuilder(WebsiteRuntime::class)
                        ->setConstructorArgs([$client, $handler, $logger, $process])
                        ->setMethods(['terminate'])
                        ->getMock();

        $client->expects($this->once())
               ->method('getNextEvent')
               ->willReturn($event);

        $handler->expects($this->once())
                ->method('canHandle')
                ->with($this->identicalTo($event))
                ->willReturn(true);
        $handler->expects($this->once())
                ->method('handle')
                ->with($this->identicalTo($event))
                ->willThrowException(new ReadFailedException());

        $logger->expects($this->once())
               ->method('exception')
               ->with($this->isInstanceOf(ReadFailedException::class));
        $logger->expects($this->once())
               ->method('info')
               ->with('Killing Lambda container. PHP-FPM process has crashed.');

        $client->expects($this->once())
               ->method('sendResponse')
               ->with($this->identicalTo($event), $this->isInstanceOf(BadGatewayHttpResponse::class));

        $runtime->expects($this->once())
                 ->method('terminate')
                 ->with(1);

        $runtime->processNextEvent();
    }

    public function testStartWithException(): void
    {
        $client = $this->getLambdaRuntimeApiClientMock();
        $exception = new \Exception('test exception');
        $handler = $this->getLambdaEventHandlerInterfaceMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $process->expects($this->once())
                ->method('start')
                ->willThrowException($exception);

        $logger->expects($this->once())
               ->method('exception')
               ->with($this->identicalTo($exception));

        $client->expects($this->once())
               ->method('sendInitializationError')
               ->with($this->identicalTo($exception));

        $runtime = $this->getMockBuilder(WebsiteRuntime::class)
                        ->setConstructorArgs([$client, $handler, $logger, $process])
                        ->setMethods(['terminate'])
                        ->getMock();

        $runtime->expects($this->once())
                 ->method('terminate')
                 ->with(1);

        $runtime->start();
    }

    public function testStartWithNoException(): void
    {
        $client = $this->getLambdaRuntimeApiClientMock();
        $handler = $this->getLambdaEventHandlerInterfaceMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $process->expects($this->once())
                ->method('start');

        $runtime = new WebsiteRuntime($client, $handler, $logger, $process);

        $runtime->start();
    }

    public function testType(): void
    {
        $this->assertSame('website', WebsiteRuntime::TYPE);
    }
}
