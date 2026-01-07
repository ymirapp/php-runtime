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
use Ymir\Runtime\Runtime;
use Ymir\Runtime\Tests\Mock\InvocationEventInterfaceMockTrait;
use Ymir\Runtime\Tests\Mock\LambdaEventHandlerInterfaceMockTrait;
use Ymir\Runtime\Tests\Mock\LambdaRuntimeApiClientMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;
use Ymir\Runtime\Tests\Mock\PhpFpmProcessMockTrait;
use Ymir\Runtime\Tests\Mock\ResponseInterfaceMockTrait;

/**
 * @covers \Ymir\Runtime\Runtime
 */
class RuntimeTest extends TestCase
{
    use InvocationEventInterfaceMockTrait;
    use LambdaEventHandlerInterfaceMockTrait;
    use LambdaRuntimeApiClientMockTrait;
    use LoggerMockTrait;
    use PhpFpmProcessMockTrait;
    use ResponseInterfaceMockTrait;

    public function testConstructorWithMaxInvocationLessThan1()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"maxInvocations" must be greater than 0');

        $client = $this->getLambdaRuntimeApiClientMock();
        $handler = $this->getLambdaEventHandlerInterfaceMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        new Runtime($client, $handler, $logger, $process, 0);
    }

    public function testProcessNextEvent()
    {
        $client = $this->getLambdaRuntimeApiClientMock();
        $event = $this->getInvocationEventInterfaceMock();
        $handler = $this->getLambdaEventHandlerInterfaceMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();
        $response = $this->getResponseInterfaceMock();

        $runtime = new Runtime($client, $handler, $logger, $process);

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

    public function testProcessNextEventWithReadFailedException()
    {
        $client = $this->getLambdaRuntimeApiClientMock();
        $event = $this->getInvocationEventInterfaceMock();
        $handler = $this->getLambdaEventHandlerInterfaceMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $runtime = $this->getMockBuilder(Runtime::class)
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

    public function testStartWithNoException()
    {
        $client = $this->getLambdaRuntimeApiClientMock();
        $handler = $this->getLambdaEventHandlerInterfaceMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $process->expects($this->once())
                ->method('start');

        $runtime = new Runtime($client, $handler, $logger, $process);

        $runtime->start();
    }
}
