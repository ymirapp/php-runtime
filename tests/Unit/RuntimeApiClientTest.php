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
use Ymir\Runtime\Exception\RuntimeApiException;
use Ymir\Runtime\Lambda\InvocationEvent\ConsoleCommandEvent;
use Ymir\Runtime\Lambda\Response\Http\HttpResponse;
use Ymir\Runtime\RuntimeApiClient;
use Ymir\Runtime\Tests\Mock\ContextMockTrait;
use Ymir\Runtime\Tests\Mock\FunctionMockTrait;
use Ymir\Runtime\Tests\Mock\InvocationEventInterfaceMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;
use Ymir\Runtime\Tests\Mock\ResponseInterfaceMockTrait;

class RuntimeApiClientTest extends TestCase
{
    use ContextMockTrait;
    use FunctionMockTrait;
    use InvocationEventInterfaceMockTrait;
    use LoggerMockTrait;
    use ResponseInterfaceMockTrait;

    public function testConstructorThrowsExceptionOnCurlInitFailure(): void
    {
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_init')->expects($this->once())->willReturn(false);

        $this->expectException(RuntimeApiException::class);
        $this->expectExceptionMessage('Failed to connect to the AWS Lambda next invocation API');

        new RuntimeApiClient('api-url', $this->getLoggerMock());
    }

    public function testGetNextEvent(): void
    {
        $handle = \curl_init();
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_init')->expects($this->once())->willReturn($handle);
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_setopt')->expects($this->any())->willReturn(true);
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_close')->expects($this->any());

        $invocationNamespace = 'Ymir\Runtime\Lambda\InvocationEvent';
        $this->getFunctionMock($invocationNamespace, 'curl_error')->expects($this->any())->willReturn('');

        $capturedSetopts = [];
        $this->getFunctionMock($invocationNamespace, 'curl_setopt')
             ->expects($this->any())
             ->willReturnCallback(function ($handle, $option, $value) use (&$capturedSetopts) {
                 $capturedSetopts[$option] = $value;

                 return true;
             });

        $this->getFunctionMock($invocationNamespace, 'curl_exec')
             ->expects($this->once())
             ->willReturnCallback(function ($handle) use (&$capturedSetopts) {
                 $headerCallback = $capturedSetopts[CURLOPT_HEADERFUNCTION];
                 $writeCallback = $capturedSetopts[CURLOPT_WRITEFUNCTION];

                 $headerCallback($handle, "Lambda-Runtime-Aws-Request-Id: request-id\n");
                 $writeCallback($handle, json_encode(['command' => 'foo']));

                 return true;
             });

        $client = new RuntimeApiClient('api-url', $this->getLoggerMock());
        $event = $client->getNextEvent();

        $this->assertInstanceOf(ConsoleCommandEvent::class, $event);
        $this->assertSame('request-id', $event->getContext()->getRequestId());

        \curl_close($handle);
    }

    public function testSendDataThrowsExceptionOnCurlError(): void
    {
        $handle = \curl_init();
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_init')->expects($this->exactly(2))->willReturn($handle);
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_setopt')->expects($this->any())->willReturn(true);
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_exec')->expects($this->once())->willReturn(false);
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_error')->expects($this->atLeastOnce())->willReturn('curl error');
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_close')->expects($this->any());
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_reset')->expects($this->any());

        $client = new RuntimeApiClient('api-url', $this->getLoggerMock());

        $this->expectException(RuntimeApiException::class);
        $this->expectExceptionMessage('Error sending data to the Lambda runtime API: curl error');

        $client->sendInitializationError(new \Exception('foo'));

        \curl_close($handle);
    }

    public function testSendDataThrowsExceptionOnJsonEncodeFailure(): void
    {
        $handle = \curl_init();
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_init')->expects($this->once())->willReturn($handle);
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_setopt')->expects($this->any())->willReturn(true);
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_close')->expects($this->any());

        $client = new RuntimeApiClient('api-url', $this->getLoggerMock());

        $this->expectException(RuntimeApiException::class);
        $this->expectExceptionMessage('Error encoding JSON data');

        // Invalid UTF-8 sequence causes json_encode to fail
        $client->sendInitializationError(new \Exception("\xB1\x31"));

        \curl_close($handle);
    }

    public function testSendError(): void
    {
        $handle = \curl_init();
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_init')->expects($this->exactly(2))->willReturn($handle);
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_error')->expects($this->any())->willReturn('');
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_exec')->expects($this->once())->willReturn(true);
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_close')->expects($this->any());
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_reset')->expects($this->any());

        $capturedData = [];
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_setopt')
             ->expects($this->any())
             ->willReturnCallback(function ($handle, $option, $value) use (&$capturedData) {
                 if (defined('CURLOPT_POSTFIELDS') && CURLOPT_POSTFIELDS === $option) {
                     $capturedData['postfields'] = $value;
                 }

                 return true;
             });

        $client = new RuntimeApiClient('api-url', $this->getLoggerMock());
        $context = $this->getContextMock();
        $context->method('getRequestId')->willReturn('request-id');

        $client->sendError($context, new \Exception('foo'));

        $this->assertArrayHasKey('postfields', $capturedData);
        $data = json_decode($capturedData['postfields'], true);

        $this->assertSame('foo', $data['errorMessage']);
        $this->assertSame('Exception', $data['errorType']);

        \curl_close($handle);
    }

    public function testSendInitializationError(): void
    {
        $handle = \curl_init();
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_init')->expects($this->exactly(2))->willReturn($handle);
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_error')->expects($this->any())->willReturn('');
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_exec')->expects($this->once())->willReturn(true);
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_close')->expects($this->any());
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_reset')->expects($this->any());

        $capturedData = [];
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_setopt')
             ->expects($this->any())
             ->willReturnCallback(function ($handle, $option, $value) use (&$capturedData) {
                 if (defined('CURLOPT_POSTFIELDS') && CURLOPT_POSTFIELDS === $option) {
                     $capturedData['postfields'] = $value;
                 }

                 return true;
             });

        $client = new RuntimeApiClient('api-url', $this->getLoggerMock());

        $client->sendInitializationError(new \Exception('foo'));

        $this->assertArrayHasKey('postfields', $capturedData);
        $data = json_decode($capturedData['postfields'], true);

        $this->assertSame('foo', $data['errorMessage']);
        $this->assertSame('Exception', $data['errorType']);

        \curl_close($handle);
    }

    public function testSendResponseAddsHeadersToForbiddenHttpResponseWhenResponseTooLarge(): void
    {
        $handle = \curl_init();
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_init')->expects($this->exactly(2))->willReturn($handle);
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_error')->expects($this->any())->willReturn('');
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_exec')->expects($this->once())->willReturn(true);
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_close')->expects($this->any());
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_reset')->expects($this->any());

        $capturedData = [];
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_setopt')
             ->expects($this->any())
             ->willReturnCallback(function ($handle, $option, $value) use (&$capturedData) {
                 if (defined('CURLOPT_POSTFIELDS') && CURLOPT_POSTFIELDS === $option) {
                     $capturedData['postfields'] = $value;
                 }

                 return true;
             });

        $client = new RuntimeApiClient('api-url', $this->getLoggerMock());
        $event = $this->getInvocationEventInterfaceMock();
        $context = $this->getContextMock();
        $response = new HttpResponse(str_repeat('a', 6000000), [], 200, '1.0', false);

        $event->method('getContext')->willReturn($context);
        $context->method('getRequestId')->willReturn('request-id');

        $client->sendResponse($event, $response);

        $this->assertArrayHasKey('postfields', $capturedData);
        $data = json_decode($capturedData['postfields'], true);

        $this->assertSame(403, $data['statusCode']);
        $this->assertArrayHasKey('headers', $data);
        $this->assertArrayHasKey('X-Request-ID', $data['headers']);
        $this->assertArrayHasKey('X-Amzn-RequestId', $data['headers']);
        $this->assertSame('request-id', $data['headers']['X-Request-ID']);
        $this->assertSame('request-id', $data['headers']['X-Amzn-RequestId']);

        \curl_close($handle);
    }

    public function testSendResponseAddsHeadersToHttpResponse(): void
    {
        $handle = \curl_init();
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_init')->expects($this->exactly(2))->willReturn($handle);
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_error')->expects($this->any())->willReturn('');
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_exec')->expects($this->once())->willReturn(true);
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_close')->expects($this->any());
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_reset')->expects($this->any());

        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_setopt')->expects($this->any())->willReturn(true);

        $client = new RuntimeApiClient('api-url', $this->getLoggerMock());
        $event = $this->getInvocationEventInterfaceMock();
        $context = $this->getContextMock();
        $response = new HttpResponse('foo');

        $event->method('getContext')->willReturn($context);
        $context->method('getRequestId')->willReturn('request-id');

        $client->sendResponse($event, $response);

        $data = $response->getResponseData();

        $this->assertArrayHasKey('multiValueHeaders', $data);
        $this->assertArrayHasKey('X-Request-ID', $data['multiValueHeaders']);
        $this->assertArrayHasKey('X-Amzn-RequestId', $data['multiValueHeaders']);
        $this->assertSame(['request-id'], $data['multiValueHeaders']['X-Request-ID']);
        $this->assertSame(['request-id'], $data['multiValueHeaders']['X-Amzn-RequestId']);

        \curl_close($handle);
    }

    public function testSendResponseWithNonHttpResponse(): void
    {
        $handle = \curl_init();
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_init')->expects($this->exactly(2))->willReturn($handle);
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_error')->expects($this->any())->willReturn('');
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_exec')->expects($this->once())->willReturn(true);
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_close')->expects($this->any());
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_reset')->expects($this->any());

        $capturedData = [];
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_setopt')
             ->expects($this->any())
             ->willReturnCallback(function ($handle, $option, $value) use (&$capturedData) {
                 if (defined('CURLOPT_POSTFIELDS') && CURLOPT_POSTFIELDS === $option) {
                     $capturedData['postfields'] = $value;
                 }

                 return true;
             });

        $client = new RuntimeApiClient('api-url', $this->getLoggerMock());
        $event = $this->getInvocationEventInterfaceMock();
        $context = $this->getContextMock();
        $response = $this->getResponseInterfaceMock();

        $event->method('getContext')->willReturn($context);
        $context->method('getRequestId')->willReturn('request-id');
        $response->method('getResponseData')->willReturn(['foo' => 'bar']);

        $client->sendResponse($event, $response);

        $this->assertArrayHasKey('postfields', $capturedData);
        $data = json_decode($capturedData['postfields'], true);

        $this->assertSame(['foo' => 'bar'], $data);

        \curl_close($handle);
    }

    public function testSendResponseWithNonHttpResponseAndLargeBody(): void
    {
        $handle = \curl_init();
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_init')->expects($this->exactly(2))->willReturn($handle);
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_error')->expects($this->any())->willReturn('');
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_exec')->expects($this->once())->willReturn(true);
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_close')->expects($this->any());
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_reset')->expects($this->any());

        $capturedData = [];
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_setopt')
             ->expects($this->any())
             ->willReturnCallback(function ($handle, $option, $value) use (&$capturedData) {
                 if (defined('CURLOPT_POSTFIELDS') && CURLOPT_POSTFIELDS === $option) {
                     $capturedData['postfields'] = $value;
                 }

                 return true;
             });

        $client = new RuntimeApiClient('api-url', $this->getLoggerMock());
        $event = $this->getInvocationEventInterfaceMock();
        $context = $this->getContextMock();
        $response = $this->getResponseInterfaceMock();

        $event->method('getContext')->willReturn($context);
        $context->method('getRequestId')->willReturn('request-id');
        $response->method('getResponseData')->willReturn(['body' => str_repeat('a', 6000000)]);

        $client->sendResponse($event, $response);

        $this->assertArrayHasKey('postfields', $capturedData);
        $data = json_decode($capturedData['postfields'], true);

        $this->assertSame(403, $data['statusCode']);
        $this->assertArrayHasKey('headers', $data);
        $this->assertSame('request-id', $data['headers']['X-Request-ID']);

        \curl_close($handle);
    }
}
