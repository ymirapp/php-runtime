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

namespace Ymir\Runtime\Tests\Unit\FastCgi;

use PHPUnit\Framework\TestCase;
use Ymir\Runtime\FastCgi\FastCgiRequest;
use Ymir\Runtime\Tests\Mock\HttpRequestEventMockTrait;

/**
 * @covers \Ymir\Runtime\FastCgi\FastCgiRequest
 */
class FastCgiRequestTest extends TestCase
{
    use HttpRequestEventMockTrait;

    public function testCreateFromInvocationEventSetsContentLengthWithTraceMethod()
    {
        $event = $this->getHttpRequestEventMock();

        $event->expects($this->once())
              ->method('getBody')
              ->willReturn('foo');

        $event->expects($this->once())
              ->method('getHeaders')
              ->willReturn([]);

        $event->expects($this->once())
              ->method('getMethod')
              ->willReturn('trace');

        $event->expects($this->once())
              ->method('getPath')
              ->willReturn('/');

        $event->expects($this->once())
              ->method('getProtocol')
              ->willReturn('HTTP/1.1');

        $event->expects($this->once())
              ->method('getQueryString')
              ->willReturn('');

        $request = FastCgiRequest::createFromInvocationEvent($event, 'foo');

        $this->assertSame('foo', $request->getContent());
        $this->assertSame([
            'GATEWAY_INTERFACE' => 'FastCGI/1.0',
            'PATH_INFO' => '/',
            'QUERY_STRING' => '',
            'REMOTE_ADDR' => '127.0.0.1',
            'REMOTE_PORT' => 80,
            'REQUEST_METHOD' => 'TRACE',
            'REQUEST_URI' => '/',
            'SCRIPT_FILENAME' => 'foo',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_SOFTWARE' => 'ymir',
            'HTTP_HOST' => 'localhost',
        ], $request->getParams());
    }

    public function testCreateFromInvocationEventSetsContentTypeWithPostMethod()
    {
        $event = $this->getHttpRequestEventMock();

        $event->expects($this->once())
              ->method('getBody')
              ->willReturn('foo');

        $event->expects($this->once())
              ->method('getHeaders')
              ->willReturn([]);

        $event->expects($this->once())
              ->method('getMethod')
              ->willReturn('post');

        $event->expects($this->once())
              ->method('getPath')
              ->willReturn('/');

        $event->expects($this->once())
              ->method('getProtocol')
              ->willReturn('HTTP/1.1');

        $event->expects($this->once())
              ->method('getQueryString')
              ->willReturn('');

        $request = FastCgiRequest::createFromInvocationEvent($event, 'foo');

        $this->assertSame('foo', $request->getContent());
        $this->assertSame([
            'GATEWAY_INTERFACE' => 'FastCGI/1.0',
            'PATH_INFO' => '/',
            'QUERY_STRING' => '',
            'REMOTE_ADDR' => '127.0.0.1',
            'REMOTE_PORT' => 80,
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/',
            'SCRIPT_FILENAME' => 'foo',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_SOFTWARE' => 'ymir',
            'CONTENT_LENGTH' => 3,
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'HTTP_HOST' => 'localhost',
        ], $request->getParams());
    }

    public function testCreateFromInvocationEventWithContentLengthHeader()
    {
        $event = $this->getHttpRequestEventMock();

        $event->expects($this->once())
              ->method('getBody')
              ->willReturn('foo');

        $event->expects($this->once())
              ->method('getHeaders')
              ->willReturn(['content-length' => [42]]);

        $event->expects($this->once())
              ->method('getMethod')
              ->willReturn('get');

        $event->expects($this->once())
              ->method('getPath')
              ->willReturn('/');

        $event->expects($this->once())
              ->method('getProtocol')
              ->willReturn('HTTP/1.1');

        $event->expects($this->once())
              ->method('getQueryString')
              ->willReturn('');

        $request = FastCgiRequest::createFromInvocationEvent($event, 'foo');

        $this->assertSame('foo', $request->getContent());
        $this->assertSame([
            'GATEWAY_INTERFACE' => 'FastCGI/1.0',
            'PATH_INFO' => '/',
            'QUERY_STRING' => '',
            'REMOTE_ADDR' => '127.0.0.1',
            'REMOTE_PORT' => 80,
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'SCRIPT_FILENAME' => 'foo',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_SOFTWARE' => 'ymir',
            'CONTENT_LENGTH' => 42,
            'HTTP_CONTENT_LENGTH' => 42,
            'HTTP_HOST' => 'localhost',
        ], $request->getParams());
    }

    public function testCreateFromInvocationEventWithContentTypeHeader()
    {
        $event = $this->getHttpRequestEventMock();

        $event->expects($this->once())
              ->method('getBody')
              ->willReturn('foo');

        $event->expects($this->once())
            ->method('getHeaders')
            ->willReturn(['content-type' => ['text/html']]);

        $event->expects($this->once())
              ->method('getMethod')
              ->willReturn('get');

        $event->expects($this->once())
              ->method('getPath')
              ->willReturn('/');

        $event->expects($this->once())
              ->method('getProtocol')
              ->willReturn('HTTP/1.1');

        $event->expects($this->once())
              ->method('getQueryString')
              ->willReturn('');

        $request = FastCgiRequest::createFromInvocationEvent($event, 'foo');

        $this->assertSame('foo', $request->getContent());
        $this->assertSame([
            'GATEWAY_INTERFACE' => 'FastCGI/1.0',
            'PATH_INFO' => '/',
            'QUERY_STRING' => '',
            'REMOTE_ADDR' => '127.0.0.1',
            'REMOTE_PORT' => 80,
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'SCRIPT_FILENAME' => 'foo',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_SOFTWARE' => 'ymir',
            'CONTENT_LENGTH' => 3,
            'CONTENT_TYPE' => 'text/html',
            'HTTP_CONTENT_TYPE' => 'text/html',
            'HTTP_HOST' => 'localhost',
        ], $request->getParams());
    }

    public function testCreateFromInvocationEventWithDefaults()
    {
        $event = $this->getHttpRequestEventMock();

        $event->expects($this->once())
              ->method('getBody')
              ->willReturn('foo');

        $event->expects($this->once())
              ->method('getHeaders')
              ->willReturn([]);

        $event->expects($this->once())
              ->method('getMethod')
              ->willReturn('get');

        $event->expects($this->once())
              ->method('getPath')
              ->willReturn('/');

        $event->expects($this->once())
              ->method('getProtocol')
              ->willReturn('HTTP/1.1');

        $event->expects($this->once())
              ->method('getQueryString')
              ->willReturn('');

        $request = FastCgiRequest::createFromInvocationEvent($event, 'foo');

        $this->assertSame('foo', $request->getContent());
        $this->assertSame([
            'GATEWAY_INTERFACE' => 'FastCGI/1.0',
            'PATH_INFO' => '/',
            'QUERY_STRING' => '',
            'REMOTE_ADDR' => '127.0.0.1',
            'REMOTE_PORT' => 80,
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'SCRIPT_FILENAME' => 'foo',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_SOFTWARE' => 'ymir',
            'CONTENT_LENGTH' => 3,
            'HTTP_HOST' => 'localhost',
        ], $request->getParams());
    }

    public function testCreateFromInvocationEventWithHostHeader()
    {
        $event = $this->getHttpRequestEventMock();

        $event->expects($this->once())
            ->method('getBody')
            ->willReturn('foo');

        $event->expects($this->once())
              ->method('getHeaders')
              ->willReturn(['host' => ['test.local']]);

        $event->expects($this->once())
            ->method('getMethod')
            ->willReturn('get');

        $event->expects($this->once())
            ->method('getPath')
            ->willReturn('/');

        $event->expects($this->once())
            ->method('getProtocol')
            ->willReturn('HTTP/1.1');

        $event->expects($this->once())
            ->method('getQueryString')
            ->willReturn('');

        $request = FastCgiRequest::createFromInvocationEvent($event, 'foo');

        $this->assertSame('foo', $request->getContent());
        $this->assertSame([
            'GATEWAY_INTERFACE' => 'FastCGI/1.0',
            'PATH_INFO' => '/',
            'QUERY_STRING' => '',
            'REMOTE_ADDR' => '127.0.0.1',
            'REMOTE_PORT' => 80,
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'SCRIPT_FILENAME' => 'foo',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_NAME' => 'test.local',
            'SERVER_PORT' => 80,
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_SOFTWARE' => 'ymir',
            'CONTENT_LENGTH' => 3,
            'HTTP_HOST' => 'test.local',
        ], $request->getParams());
    }

    public function testCreateFromInvocationEventWithQueryString()
    {
        $event = $this->getHttpRequestEventMock();

        $event->expects($this->once())
              ->method('getBody')
              ->willReturn('foo');

        $event->expects($this->once())
              ->method('getHeaders')
              ->willReturn([]);

        $event->expects($this->once())
              ->method('getMethod')
              ->willReturn('get');

        $event->expects($this->once())
              ->method('getPath')
              ->willReturn('/');

        $event->expects($this->once())
              ->method('getProtocol')
              ->willReturn('HTTP/1.1');

        $event->expects($this->once())
            ->method('getQueryString')
            ->willReturn('test');

        $request = FastCgiRequest::createFromInvocationEvent($event, 'foo');

        $this->assertSame('foo', $request->getContent());
        $this->assertSame([
            'GATEWAY_INTERFACE' => 'FastCGI/1.0',
            'PATH_INFO' => '/',
            'QUERY_STRING' => 'test',
            'REMOTE_ADDR' => '127.0.0.1',
            'REMOTE_PORT' => 80,
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/?test',
            'SCRIPT_FILENAME' => 'foo',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_SOFTWARE' => 'ymir',
            'CONTENT_LENGTH' => 3,
            'HTTP_HOST' => 'localhost',
        ], $request->getParams());
    }

    public function testCreateFromInvocationEventWithXForwardedHostHeader()
    {
        $event = $this->getHttpRequestEventMock();

        $event->expects($this->once())
            ->method('getBody')
            ->willReturn('foo');

        $event->expects($this->once())
            ->method('getHeaders')
            ->willReturn(['x-forwarded-host' => ['test.local']]);

        $event->expects($this->once())
            ->method('getMethod')
            ->willReturn('get');

        $event->expects($this->once())
            ->method('getPath')
            ->willReturn('/');

        $event->expects($this->once())
            ->method('getProtocol')
            ->willReturn('HTTP/1.1');

        $event->expects($this->once())
            ->method('getQueryString')
            ->willReturn('');

        $request = FastCgiRequest::createFromInvocationEvent($event, 'foo');

        $this->assertSame('foo', $request->getContent());
        $this->assertSame([
            'GATEWAY_INTERFACE' => 'FastCGI/1.0',
            'PATH_INFO' => '/',
            'QUERY_STRING' => '',
            'REMOTE_ADDR' => '127.0.0.1',
            'REMOTE_PORT' => 80,
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'SCRIPT_FILENAME' => 'foo',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_NAME' => 'test.local',
            'SERVER_PORT' => 80,
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_SOFTWARE' => 'ymir',
            'CONTENT_LENGTH' => 3,
            'HTTP_X_FORWARDED_HOST' => 'test.local',
            'HTTP_HOST' => 'test.local',
        ], $request->getParams());
    }

    public function testCreateFromInvocationEventWithXForwardedProto()
    {
        $event = $this->getHttpRequestEventMock();

        $event->expects($this->once())
              ->method('getBody')
              ->willReturn('foo');

        $event->expects($this->once())
              ->method('getHeaders')
              ->willReturn(['x-forwarded-proto' => ['https']]);

        $event->expects($this->once())
              ->method('getMethod')
              ->willReturn('get');

        $event->expects($this->once())
              ->method('getPath')
              ->willReturn('/');

        $event->expects($this->once())
              ->method('getProtocol')
              ->willReturn('HTTP/1.1');

        $event->expects($this->once())
              ->method('getQueryString')
              ->willReturn('');

        $request = FastCgiRequest::createFromInvocationEvent($event, 'foo');

        $this->assertSame('foo', $request->getContent());
        $this->assertSame([
            'GATEWAY_INTERFACE' => 'FastCGI/1.0',
            'PATH_INFO' => '/',
            'QUERY_STRING' => '',
            'REMOTE_ADDR' => '127.0.0.1',
            'REMOTE_PORT' => 80,
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'SCRIPT_FILENAME' => 'foo',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_SOFTWARE' => 'ymir',
            'HTTPS' => 'on',
            'CONTENT_LENGTH' => 3,
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_HOST' => 'localhost',
        ], $request->getParams());
    }

    public function testGetContent()
    {
        $request = new FastCgiRequest('foo', []);

        $this->assertSame('foo', $request->getContent());
    }

    public function testGetContentLengthCastsValue()
    {
        $request = new FastCgiRequest('', ['content_length' => '1']);

        $this->assertSame(1, $request->getContentLength());
    }

    public function testGetContentLengthDefaultValue()
    {
        $this->assertSame(0, (new FastCgiRequest())->getContentLength());
    }

    public function testGetContentLengthWithValue()
    {
        $request = new FastCgiRequest('', ['content_length' => 42]);

        $this->assertSame(42, $request->getContentLength());
    }

    public function testGetContentTypeCastsValue()
    {
        $request = new FastCgiRequest('', ['content_type' => 1]);

        $this->assertSame('1', $request->getContentType());
    }

    public function testGetContentTypeDefaultValue()
    {
        $this->assertSame('application/x-www-form-urlencoded', (new FastCgiRequest())->getContentType());
    }

    public function testGetContentTypeWithValue()
    {
        $request = new FastCgiRequest('', ['content_type' => 'text/html']);

        $this->assertSame('text/html', $request->getContentType());
    }

    public function testGetCustomVars()
    {
        $this->assertSame(['FOO' => 'bar'], (new FastCgiRequest('', ['foo' => 'bar']))->getCustomVars());
    }

    public function testGetGatewayInterfaceCastsValue()
    {
        $request = new FastCgiRequest('', ['gateway_interface' => 1]);

        $this->assertSame('1', $request->getGatewayInterface());
    }

    public function testGetGatewayInterfaceDefaultValue()
    {
        $this->assertSame('FastCGI/1.0', (new FastCgiRequest())->getGatewayInterface());
    }

    public function testGetGatewayInterfaceWithValue()
    {
        $request = new FastCgiRequest('', ['gateway_interface' => 'FastCGI/2.0']);

        $this->assertSame('FastCGI/2.0', $request->getGatewayInterface());
    }

    public function testGetParams()
    {
        $this->assertSame(['FOO' => 'bar'], (new FastCgiRequest('', ['foo' => 'bar']))->getParams());
    }

    public function testGetPassThroughCallback()
    {
        $this->assertSame([], (new FastCgiRequest())->getPassThroughCallbacks());
    }

    public function testGetRemoteAddressCastsValue()
    {
        $request = new FastCgiRequest('', ['remote_addr' => 1]);

        $this->assertSame('1', $request->getRemoteAddress());
    }

    public function testGetRemoteAddressDefaultValue()
    {
        $this->assertSame('192.168.0.1', (new FastCgiRequest())->getRemoteAddress());
    }

    public function testGetRemoteAddressWithValue()
    {
        $request = new FastCgiRequest('', ['remote_addr' => '192.168.1.1']);

        $this->assertSame('192.168.1.1', $request->getRemoteAddress());
    }

    public function testGetRemotePortCastsValue()
    {
        $request = new FastCgiRequest('', ['remote_port' => '1']);

        $this->assertSame(1, $request->getRemotePort());
    }

    public function testGetRemotePortDefaultValue()
    {
        $this->assertSame(9985, (new FastCgiRequest())->getRemotePort());
    }

    public function testGetRemotePortWithValue()
    {
        $request = new FastCgiRequest('', ['remote_port' => 42]);

        $this->assertSame(42, $request->getRemotePort());
    }

    public function testGetRequestMethodCastsValue()
    {
        $request = new FastCgiRequest('', ['request_method' => 1]);

        $this->assertSame('1', $request->getRequestMethod());
    }

    public function testGetRequestMethodDefaultValue()
    {
        $this->assertSame('GET', (new FastCgiRequest())->getRequestMethod());
    }

    public function testGetRequestMethodWithValue()
    {
        $request = new FastCgiRequest('', ['request_method' => 'post']);

        $this->assertSame('POST', $request->getRequestMethod());
    }

    public function testGetRequestUriCastsValue()
    {
        $request = new FastCgiRequest('', ['request_uri' => 1]);

        $this->assertSame('1', $request->getRequestUri());
    }

    public function testGetRequestUriDefaultValue()
    {
        $this->assertSame('', (new FastCgiRequest())->getRequestUri());
    }

    public function testGetRequestUriWithValue()
    {
        $request = new FastCgiRequest('', ['request_uri' => '/']);

        $this->assertSame('/', $request->getRequestUri());
    }

    public function testGetResponseCallbacks()
    {
        $this->assertSame([], (new FastCgiRequest())->getResponseCallbacks());
    }

    public function testGetScriptFilenameCastsValue()
    {
        $request = new FastCgiRequest('', ['script_filename' => 1]);

        $this->assertSame('1', $request->getScriptFilename());
    }

    public function testGetScriptFilenameDefaultValue()
    {
        $this->assertSame('', (new FastCgiRequest())->getScriptFilename());
    }

    public function testGetScriptFilenameWithValue()
    {
        $request = new FastCgiRequest('', ['script_filename' => 'index.php']);

        $this->assertSame('index.php', $request->getScriptFilename());
    }

    public function testGetServerAddressCastsValue()
    {
        $request = new FastCgiRequest('', ['server_addr' => 1]);

        $this->assertSame('1', $request->getServerAddress());
    }

    public function testGetServerAddressDefaultValue()
    {
        $this->assertSame('127.0.0.1', (new FastCgiRequest())->getServerAddress());
    }

    public function testGetServerAddressWithValue()
    {
        $request = new FastCgiRequest('', ['server_addr' => '127.0.1.1']);

        $this->assertSame('127.0.1.1', $request->getServerAddress());
    }

    public function testGetServerNameCastsValue()
    {
        $request = new FastCgiRequest('', ['server_name' => 1]);

        $this->assertSame('1', $request->getServerName());
    }

    public function testGetServerNameDefaultValue()
    {
        $this->assertSame('localhost', (new FastCgiRequest())->getServerName());
    }

    public function testGetServerNameWithValue()
    {
        $request = new FastCgiRequest('', ['server_name' => 'ymir.local']);

        $this->assertSame('ymir.local', $request->getServerName());
    }

    public function testGetServerPortCastsValue()
    {
        $request = new FastCgiRequest('', ['server_port' => '1']);

        $this->assertSame(1, $request->getServerPort());
    }

    public function testGetServerPortDefaultValue()
    {
        $this->assertSame(80, (new FastCgiRequest())->getServerPort());
    }

    public function testGetServerPortWithValue()
    {
        $request = new FastCgiRequest('', ['server_port' => 22]);

        $this->assertSame(22, $request->getServerPort());
    }

    public function testGetServerProtocolCastsValue()
    {
        $request = new FastCgiRequest('', ['server_protocol' => 1]);

        $this->assertSame('1', $request->getServerProtocol());
    }

    public function testGetServerProtocolDefaultValue()
    {
        $this->assertSame('HTTP/1.1', (new FastCgiRequest())->getServerProtocol());
    }

    public function testGetServerProtocolWithValue()
    {
        $request = new FastCgiRequest('', ['server_protocol' => 'HTTP/1.0']);

        $this->assertSame('HTTP/1.0', $request->getServerProtocol());
    }

    public function testGetServerSoftwareCastsValue()
    {
        $request = new FastCgiRequest('', ['server_software' => 1]);

        $this->assertSame('1', $request->getServerSoftware());
    }

    public function testGetServerSoftwareDefaultValue()
    {
        $this->assertSame('ymir', (new FastCgiRequest())->getServerSoftware());
    }

    public function testGetServerSoftwareWithValue()
    {
        $request = new FastCgiRequest('', ['server_software' => 'foo']);

        $this->assertSame('foo', $request->getServerSoftware());
    }
}
