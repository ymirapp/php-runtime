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
use Ymir\Runtime\Exception\PhpFpm\PhpFpmException;
use Ymir\Runtime\Exception\PhpFpm\PhpFpmProcessException;
use Ymir\Runtime\Exception\PhpFpm\PhpFpmTimeoutException;
use Ymir\Runtime\Lambda\InvocationEvent\HttpRequestEvent;
use Ymir\Runtime\Lambda\InvocationEvent\InvocationContext;
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

    private const ERRORS_FILE = '/tmp/.ymir/runtime-errors.jsonl';

    protected function setUp(): void
    {
        if (!is_dir(dirname(self::ERRORS_FILE))) {
            mkdir(dirname(self::ERRORS_FILE));
        }
    }

    protected function tearDown(): void
    {
        if (is_file(self::ERRORS_FILE)) {
            unlink(self::ERRORS_FILE);
        }
    }

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

    public function testProcessNextEventWithPhpFpmProcessExceptionRecordsCrashForHttpRequestEvent(): void
    {
        $this->processNextEventWithPhpFpmException(new HttpRequestEvent(new InvocationContext('request-id'), ['httpMethod' => 'GET', 'path' => '/export']), new PhpFpmProcessException('PHP-FPM ended the request without a response'));

        $lines = file(self::ERRORS_FILE, FILE_IGNORE_NEW_LINES);
        $record = json_decode($lines[0], true);

        $this->assertCount(1, $lines);
        $this->assertIsInt($record['timestamp']);
        unset($record['timestamp']);
        $this->assertSame([
            'type' => 'crash',
            'message' => 'PHP-FPM ended the request without a response',
            'method' => 'GET',
            'path' => '/export',
            'requestId' => 'request-id',
        ], $record);
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

        $this->assertFalse(is_file(self::ERRORS_FILE));
    }

    public function testProcessNextEventWithPhpFpmTimeoutExceptionDoesNotRecordTimeoutWhenErrorsFileIsFull(): void
    {
        file_put_contents(self::ERRORS_FILE, str_repeat('a', 65536));

        $this->processNextEventWithPhpFpmException(new HttpRequestEvent(new InvocationContext('request-id'), ['httpMethod' => 'GET', 'path' => '/slow']), new PhpFpmTimeoutException('PHP-FPM request timed out after 28000ms'));

        $this->assertSame(str_repeat('a', 65536), file_get_contents(self::ERRORS_FILE));
    }

    public function testProcessNextEventWithPhpFpmTimeoutExceptionRecordsTimeoutForHttpRequestEvent(): void
    {
        file_put_contents(self::ERRORS_FILE, "{\"type\":\"timeout\"}\n");

        $this->processNextEventWithPhpFpmException(new HttpRequestEvent(new InvocationContext('request-id'), ['httpMethod' => 'post', 'path' => '/slow', 'queryStringParameters' => ['token' => 'secret']]), new PhpFpmTimeoutException('PHP-FPM request timed out after 28000ms'));

        $lines = file(self::ERRORS_FILE, FILE_IGNORE_NEW_LINES);
        $record = json_decode($lines[1], true);

        $this->assertCount(2, $lines);
        $this->assertSame('{"type":"timeout"}', $lines[0]);
        $this->assertIsInt($record['timestamp']);
        unset($record['timestamp']);
        $this->assertSame([
            'type' => 'timeout',
            'message' => 'PHP-FPM request timed out after 28000ms',
            'method' => 'POST',
            'path' => '/slow',
            'requestId' => 'request-id',
        ], $record);
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

    private function processNextEventWithPhpFpmException(HttpRequestEvent $event, PhpFpmException $exception): void
    {
        $client = $this->getLambdaRuntimeApiClientMock();
        $handler = $this->getLambdaEventHandlerInterfaceMock();

        $runtime = $this->getMockBuilder(WebsiteRuntime::class)
                        ->setConstructorArgs([$client, $handler, $this->getLoggerMock(), $this->getPhpFpmProcessMock()])
                        ->setMethods(['terminate'])
                        ->getMock();
        $runtime->method('terminate')
                ->willReturnCallback(function (): void {
                    $this->assertFileExists(self::ERRORS_FILE);
                });

        $client->expects($this->once())
               ->method('getNextEvent')
               ->willReturn($event);

        $handler->expects($this->once())
                ->method('canHandle')
                ->willReturn(true);
        $handler->expects($this->once())
                ->method('handle')
                ->willThrowException($exception);

        $client->expects($this->once())
               ->method('sendResponse')
               ->with($this->identicalTo($event));

        $runtime->processNextEvent();
    }
}
