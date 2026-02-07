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
use Ymir\Runtime\Exception\InvalidConfigurationException;
use Ymir\Runtime\Exception\PhpFpm\PhpFpmProcessException;
use Ymir\Runtime\Exception\PhpFpm\PhpFpmTimeoutException;
use Ymir\Runtime\Lambda\Response\Http\BadGatewayHttpResponse;
use Ymir\Runtime\Lambda\Response\Http\GatewayTimeoutHttpResponse;
use Ymir\Runtime\Tests\Mock\InvocationEventInterfaceMockTrait;
use Ymir\Runtime\Tests\Mock\LambdaEventHandlerInterfaceMockTrait;
use Ymir\Runtime\Tests\Mock\LambdaRuntimeApiClientMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;
use Ymir\Runtime\Tests\Mock\PhpFpmProcessMockTrait;
use Ymir\Runtime\Tests\Mock\ResponseInterfaceMockTrait;
use Ymir\Runtime\WebsiteRuntime;

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
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('"maxInvocations" must be greater than 0');

        $client = $this->getLambdaRuntimeApiClientMock();
        $handler = $this->getLambdaEventHandlerInterfaceMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        new WebsiteRuntime($client, $handler, $logger, $process, 0);
    }

    public function testCreateFromApplication(): void
    {
        $application = $this->getMockBuilder(\Ymir\Runtime\Application\ApplicationInterface::class)->getMock();
        $context = new \Ymir\Runtime\RuntimeContext($this->getLoggerMock(), $this->getLambdaRuntimeApiClientMock(), 'us-east-1', '/var/task');
        $handlers = $this->getMockBuilder(\Ymir\Runtime\Lambda\Handler\LambdaEventHandlerCollection::class)
                         ->disableOriginalConstructor()
                         ->getMock();

        $application->expects($this->once())
                    ->method('getContext')
                    ->willReturn($context);
        $application->expects($this->once())
                    ->method('getWebsiteHandlers')
                    ->willReturn($handlers);

        $this->assertInstanceOf(WebsiteRuntime::class, WebsiteRuntime::createFromApplication($application));
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
               ->with('Function has processed 1 invocation events, killing lambda function');

        $runtime->expects($this->once())
                 ->method('terminate')
                 ->with(0);

        $runtime->processNextEvent();
    }

    public function testProcessNextEventWithPhpFpmProcessException(): void
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
                ->willThrowException(new PhpFpmProcessException('test crash'));

        $logger->expects($this->once())
               ->method('exception')
               ->with($this->isInstanceOf(PhpFpmProcessException::class));
        $logger->expects($this->once())
               ->method('info')
               ->with('PHP-FPM process has crashed, killing lambda function');

        $client->expects($this->once())
               ->method('sendResponse')
               ->with($this->identicalTo($event), $this->isInstanceOf(BadGatewayHttpResponse::class));

        $runtime->expects($this->once())
                 ->method('terminate')
                 ->with(1);

        $runtime->processNextEvent();
    }

    public function testProcessNextEventWithPhpFpmTimeoutException(): void
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
                ->willThrowException(new PhpFpmTimeoutException('test timeout'));

        $client->expects($this->once())
               ->method('sendResponse')
               ->with($this->identicalTo($event), $this->isInstanceOf(GatewayTimeoutHttpResponse::class));

        $runtime->expects($this->never())
                 ->method('terminate');

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
