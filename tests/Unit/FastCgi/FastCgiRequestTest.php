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
use Ymir\Runtime\Tests\Mock\FunctionMockTrait;
use Ymir\Runtime\Tests\Mock\HttpRequestEventMockTrait;

class FastCgiRequestTest extends TestCase
{
    use FunctionMockTrait;
    use HttpRequestEventMockTrait;

    public function testCreateFromInvocationEventSetsContentLengthWithTraceMethod(): void
    {
        $event = $this->getHttpRequestEventMock();
        $getcwd = $this->getFunctionMock($this->getNamespace(FastCgiRequest::class), 'getcwd');
        $microtime = $this->getFunctionMock($this->getNamespace(FastCgiRequest::class), 'microtime');
        $time = $this->getFunctionMock($this->getNamespace(FastCgiRequest::class), 'time');

        $getcwd->expects($this->once())
               ->willReturn('/tmp');

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

        $event->expects($this->once())
              ->method('getSourceIp')
              ->willReturn('127.0.0.1');

        $microtime->expects($this->once())
                  ->willReturn(1617733986.080936);

        $time->expects($this->once())
             ->willReturn(1617733986);

        $request = FastCgiRequest::createFromInvocationEvent($event, '/tmp/foo.php');

        $this->assertSame('foo', $request->getContent());
        $this->assertSame([
            'DOCUMENT_ROOT' => '/tmp',
            'GATEWAY_INTERFACE' => 'FastCGI/1.0',
            'HTTP_HOST' => 'localhost',
            'PATH_INFO' => '',
            'PHP_SELF' => '/foo.php',
            'QUERY_STRING' => '',
            'REMOTE_ADDR' => '127.0.0.1',
            'REMOTE_PORT' => 80,
            'REQUEST_METHOD' => 'TRACE',
            'REQUEST_TIME' => 1617733986,
            'REQUEST_TIME_FLOAT' => 1617733986.080936,
            'REQUEST_URI' => '/',
            'SCRIPT_FILENAME' => '/tmp/foo.php',
            'SCRIPT_NAME' => '/foo.php',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_SOFTWARE' => 'ymir',
        ], $request->getParams());
    }

    public function testCreateFromInvocationEventSetsContentTypeWithPostMethod(): void
    {
        $event = $this->getHttpRequestEventMock();
        $getcwd = $this->getFunctionMock($this->getNamespace(FastCgiRequest::class), 'getcwd');
        $microtime = $this->getFunctionMock($this->getNamespace(FastCgiRequest::class), 'microtime');
        $time = $this->getFunctionMock($this->getNamespace(FastCgiRequest::class), 'time');

        $getcwd->expects($this->once())
               ->willReturn('/tmp');

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

        $event->expects($this->once())
              ->method('getSourceIp')
              ->willReturn('127.0.0.1');

        $microtime->expects($this->once())
                  ->willReturn(1617733986.080936);

        $time->expects($this->once())
             ->willReturn(1617733986);

        $request = FastCgiRequest::createFromInvocationEvent($event, '/tmp/foo.php');

        $this->assertSame('foo', $request->getContent());
        $this->assertSame([
            'CONTENT_LENGTH' => 3,
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'DOCUMENT_ROOT' => '/tmp',
            'GATEWAY_INTERFACE' => 'FastCGI/1.0',
            'HTTP_HOST' => 'localhost',
            'PATH_INFO' => '',
            'PHP_SELF' => '/foo.php',
            'QUERY_STRING' => '',
            'REMOTE_ADDR' => '127.0.0.1',
            'REMOTE_PORT' => 80,
            'REQUEST_METHOD' => 'POST',
            'REQUEST_TIME' => 1617733986,
            'REQUEST_TIME_FLOAT' => 1617733986.080936,
            'REQUEST_URI' => '/',
            'SCRIPT_FILENAME' => '/tmp/foo.php',
            'SCRIPT_NAME' => '/foo.php',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_SOFTWARE' => 'ymir',
        ], $request->getParams());
    }

    public function testCreateFromInvocationEventWithContentLengthHeader(): void
    {
        $event = $this->getHttpRequestEventMock();
        $getcwd = $this->getFunctionMock($this->getNamespace(FastCgiRequest::class), 'getcwd');
        $microtime = $this->getFunctionMock($this->getNamespace(FastCgiRequest::class), 'microtime');
        $time = $this->getFunctionMock($this->getNamespace(FastCgiRequest::class), 'time');

        $getcwd->expects($this->once())
               ->willReturn('/tmp');

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

        $event->expects($this->once())
              ->method('getSourceIp')
              ->willReturn('127.0.0.1');

        $microtime->expects($this->once())
            ->willReturn(1617733986.080936);

        $time->expects($this->once())
            ->willReturn(1617733986);

        $request = FastCgiRequest::createFromInvocationEvent($event, '/tmp/foo.php');

        $this->assertSame('foo', $request->getContent());
        $this->assertSame([
            'CONTENT_LENGTH' => 42,
            'DOCUMENT_ROOT' => '/tmp',
            'GATEWAY_INTERFACE' => 'FastCGI/1.0',
            'HTTP_CONTENT_LENGTH' => 42,
            'HTTP_HOST' => 'localhost',
            'PATH_INFO' => '',
            'PHP_SELF' => '/foo.php',
            'QUERY_STRING' => '',
            'REMOTE_ADDR' => '127.0.0.1',
            'REMOTE_PORT' => 80,
            'REQUEST_METHOD' => 'GET',
            'REQUEST_TIME' => 1617733986,
            'REQUEST_TIME_FLOAT' => 1617733986.080936,
            'REQUEST_URI' => '/',
            'SCRIPT_FILENAME' => '/tmp/foo.php',
            'SCRIPT_NAME' => '/foo.php',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_SOFTWARE' => 'ymir',
        ], $request->getParams());
    }

    public function testCreateFromInvocationEventWithContentTypeHeader(): void
    {
        $event = $this->getHttpRequestEventMock();
        $getcwd = $this->getFunctionMock($this->getNamespace(FastCgiRequest::class), 'getcwd');
        $microtime = $this->getFunctionMock($this->getNamespace(FastCgiRequest::class), 'microtime');
        $time = $this->getFunctionMock($this->getNamespace(FastCgiRequest::class), 'time');

        $getcwd->expects($this->once())
               ->willReturn('/tmp');

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

        $event->expects($this->once())
              ->method('getSourceIp')
              ->willReturn('127.0.0.1');

        $microtime->expects($this->once())
            ->willReturn(1617733986.080936);

        $time->expects($this->once())
            ->willReturn(1617733986);

        $request = FastCgiRequest::createFromInvocationEvent($event, '/tmp/foo.php');

        $this->assertSame('foo', $request->getContent());
        $this->assertSame([
            'CONTENT_LENGTH' => 3,
            'CONTENT_TYPE' => 'text/html',
            'DOCUMENT_ROOT' => '/tmp',
            'GATEWAY_INTERFACE' => 'FastCGI/1.0',
            'HTTP_CONTENT_TYPE' => 'text/html',
            'HTTP_HOST' => 'localhost',
            'PATH_INFO' => '',
            'PHP_SELF' => '/foo.php',
            'QUERY_STRING' => '',
            'REMOTE_ADDR' => '127.0.0.1',
            'REMOTE_PORT' => 80,
            'REQUEST_METHOD' => 'GET',
            'REQUEST_TIME' => 1617733986,
            'REQUEST_TIME_FLOAT' => 1617733986.080936,
            'REQUEST_URI' => '/',
            'SCRIPT_FILENAME' => '/tmp/foo.php',
            'SCRIPT_NAME' => '/foo.php',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_SOFTWARE' => 'ymir',
        ], $request->getParams());
    }

    public function testCreateFromInvocationEventWithDefaults(): void
    {
        $event = $this->getHttpRequestEventMock();
        $getcwd = $this->getFunctionMock($this->getNamespace(FastCgiRequest::class), 'getcwd');
        $microtime = $this->getFunctionMock($this->getNamespace(FastCgiRequest::class), 'microtime');
        $time = $this->getFunctionMock($this->getNamespace(FastCgiRequest::class), 'time');

        $getcwd->expects($this->once())
               ->willReturn('/tmp');

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

        $event->expects($this->once())
              ->method('getSourceIp')
              ->willReturn('127.0.0.1');

        $microtime->expects($this->once())
                  ->willReturn(1617733986.080936);

        $time->expects($this->once())
             ->willReturn(1617733986);

        $request = FastCgiRequest::createFromInvocationEvent($event, '/tmp/foo.php');

        $this->assertSame('foo', $request->getContent());
        $this->assertSame([
            'CONTENT_LENGTH' => 3,
            'DOCUMENT_ROOT' => '/tmp',
            'GATEWAY_INTERFACE' => 'FastCGI/1.0',
            'HTTP_HOST' => 'localhost',
            'PATH_INFO' => '',
            'PHP_SELF' => '/foo.php',
            'QUERY_STRING' => '',
            'REMOTE_ADDR' => '127.0.0.1',
            'REMOTE_PORT' => 80,
            'REQUEST_METHOD' => 'GET',
            'REQUEST_TIME' => 1617733986,
            'REQUEST_TIME_FLOAT' => 1617733986.080936,
            'REQUEST_URI' => '/',
            'SCRIPT_FILENAME' => '/tmp/foo.php',
            'SCRIPT_NAME' => '/foo.php',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_SOFTWARE' => 'ymir',
        ], $request->getParams());
    }

    public function testCreateFromInvocationEventWithHostHeader(): void
    {
        $event = $this->getHttpRequestEventMock();
        $getcwd = $this->getFunctionMock($this->getNamespace(FastCgiRequest::class), 'getcwd');
        $microtime = $this->getFunctionMock($this->getNamespace(FastCgiRequest::class), 'microtime');
        $time = $this->getFunctionMock($this->getNamespace(FastCgiRequest::class), 'time');

        $getcwd->expects($this->once())
               ->willReturn('/tmp');

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

        $event->expects($this->once())
              ->method('getSourceIp')
              ->willReturn('127.0.0.1');

        $microtime->expects($this->once())
                  ->willReturn(1617733986.080936);

        $time->expects($this->once())
             ->willReturn(1617733986);

        $request = FastCgiRequest::createFromInvocationEvent($event, '/tmp/foo.php');

        $this->assertSame('foo', $request->getContent());
        $this->assertSame([
            'CONTENT_LENGTH' => 3,
            'DOCUMENT_ROOT' => '/tmp',
            'GATEWAY_INTERFACE' => 'FastCGI/1.0',
            'HTTP_HOST' => 'test.local',
            'PATH_INFO' => '',
            'PHP_SELF' => '/foo.php',
            'QUERY_STRING' => '',
            'REMOTE_ADDR' => '127.0.0.1',
            'REMOTE_PORT' => 80,
            'REQUEST_METHOD' => 'GET',
            'REQUEST_TIME' => 1617733986,
            'REQUEST_TIME_FLOAT' => 1617733986.080936,
            'REQUEST_URI' => '/',
            'SCRIPT_FILENAME' => '/tmp/foo.php',
            'SCRIPT_NAME' => '/foo.php',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_NAME' => 'test.local',
            'SERVER_PORT' => 80,
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_SOFTWARE' => 'ymir',
        ], $request->getParams());
    }

    public function testCreateFromInvocationEventWithPathAndQueryString(): void
    {
        $event = $this->getHttpRequestEventMock();
        $getcwd = $this->getFunctionMock($this->getNamespace(FastCgiRequest::class), 'getcwd');
        $microtime = $this->getFunctionMock($this->getNamespace(FastCgiRequest::class), 'microtime');
        $time = $this->getFunctionMock($this->getNamespace(FastCgiRequest::class), 'time');

        $getcwd->expects($this->once())
               ->willReturn('/tmp');

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
              ->willReturn('/bar');

        $event->expects($this->once())
              ->method('getProtocol')
              ->willReturn('HTTP/1.1');

        $event->expects($this->once())
              ->method('getQueryString')
              ->willReturn('test');

        $event->expects($this->once())
              ->method('getSourceIp')
              ->willReturn('127.0.0.1');

        $microtime->expects($this->once())
                  ->willReturn(1617733986.080936);

        $time->expects($this->once())
             ->willReturn(1617733986);

        $request = FastCgiRequest::createFromInvocationEvent($event, '/tmp/foo.php');

        $this->assertSame('foo', $request->getContent());
        $this->assertSame([
            'CONTENT_LENGTH' => 3,
            'DOCUMENT_ROOT' => '/tmp',
            'GATEWAY_INTERFACE' => 'FastCGI/1.0',
            'HTTP_HOST' => 'localhost',
            'PATH_INFO' => '',
            'PHP_SELF' => '/foo.php/bar',
            'QUERY_STRING' => 'test',
            'REMOTE_ADDR' => '127.0.0.1',
            'REMOTE_PORT' => 80,
            'REQUEST_METHOD' => 'GET',
            'REQUEST_TIME' => 1617733986,
            'REQUEST_TIME_FLOAT' => 1617733986.080936,
            'REQUEST_URI' => '/bar?test',
            'SCRIPT_FILENAME' => '/tmp/foo.php',
            'SCRIPT_NAME' => '/foo.php',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_SOFTWARE' => 'ymir',
        ], $request->getParams());
    }

    public function testCreateFromInvocationEventWithPathInfoAndQueryString(): void
    {
        $event = $this->getHttpRequestEventMock();
        $getcwd = $this->getFunctionMock($this->getNamespace(FastCgiRequest::class), 'getcwd');
        $microtime = $this->getFunctionMock($this->getNamespace(FastCgiRequest::class), 'microtime');
        $time = $this->getFunctionMock($this->getNamespace(FastCgiRequest::class), 'time');

        $getcwd->expects($this->once())
               ->willReturn('/tmp');

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
              ->willReturn('/foo.php/bar');

        $event->expects($this->once())
              ->method('getProtocol')
              ->willReturn('HTTP/1.1');

        $event->expects($this->once())
              ->method('getQueryString')
              ->willReturn('test');

        $event->expects($this->once())
              ->method('getSourceIp')
              ->willReturn('127.0.0.1');

        $microtime->expects($this->once())
                  ->willReturn(1617733986.080936);

        $time->expects($this->once())
             ->willReturn(1617733986);

        $request = FastCgiRequest::createFromInvocationEvent($event, '/tmp/foo.php');

        $this->assertSame('foo', $request->getContent());
        $this->assertSame([
            'CONTENT_LENGTH' => 3,
            'DOCUMENT_ROOT' => '/tmp',
            'GATEWAY_INTERFACE' => 'FastCGI/1.0',
            'HTTP_HOST' => 'localhost',
            'PATH_INFO' => '/bar',
            'PHP_SELF' => '/foo.php/bar',
            'QUERY_STRING' => 'test',
            'REMOTE_ADDR' => '127.0.0.1',
            'REMOTE_PORT' => 80,
            'REQUEST_METHOD' => 'GET',
            'REQUEST_TIME' => 1617733986,
            'REQUEST_TIME_FLOAT' => 1617733986.080936,
            'REQUEST_URI' => '/foo.php/bar?test',
            'SCRIPT_FILENAME' => '/tmp/foo.php',
            'SCRIPT_NAME' => '/foo.php',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_SOFTWARE' => 'ymir',
        ], $request->getParams());
    }

    public function testCreateFromInvocationEventWithQueryString(): void
    {
        $event = $this->getHttpRequestEventMock();
        $getcwd = $this->getFunctionMock($this->getNamespace(FastCgiRequest::class), 'getcwd');
        $microtime = $this->getFunctionMock($this->getNamespace(FastCgiRequest::class), 'microtime');
        $time = $this->getFunctionMock($this->getNamespace(FastCgiRequest::class), 'time');

        $getcwd->expects($this->once())
               ->willReturn('/tmp');

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

        $event->expects($this->once())
              ->method('getSourceIp')
              ->willReturn('127.0.0.1');

        $microtime->expects($this->once())
                  ->willReturn(1617733986.080936);

        $time->expects($this->once())
             ->willReturn(1617733986);

        $request = FastCgiRequest::createFromInvocationEvent($event, '/tmp/foo.php');

        $this->assertSame('foo', $request->getContent());
        $this->assertSame([
            'CONTENT_LENGTH' => 3,
            'DOCUMENT_ROOT' => '/tmp',
            'GATEWAY_INTERFACE' => 'FastCGI/1.0',
            'HTTP_HOST' => 'localhost',
            'PATH_INFO' => '',
            'PHP_SELF' => '/foo.php',
            'QUERY_STRING' => 'test',
            'REMOTE_ADDR' => '127.0.0.1',
            'REMOTE_PORT' => 80,
            'REQUEST_METHOD' => 'GET',
            'REQUEST_TIME' => 1617733986,
            'REQUEST_TIME_FLOAT' => 1617733986.080936,
            'REQUEST_URI' => '/?test',
            'SCRIPT_FILENAME' => '/tmp/foo.php',
            'SCRIPT_NAME' => '/foo.php',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_SOFTWARE' => 'ymir',
        ], $request->getParams());
    }

    public function testCreateFromInvocationEventWithXForwardedHostHeader(): void
    {
        $event = $this->getHttpRequestEventMock();
        $getcwd = $this->getFunctionMock($this->getNamespace(FastCgiRequest::class), 'getcwd');
        $microtime = $this->getFunctionMock($this->getNamespace(FastCgiRequest::class), 'microtime');
        $time = $this->getFunctionMock($this->getNamespace(FastCgiRequest::class), 'time');

        $getcwd->expects($this->once())
               ->willReturn('/tmp');

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

        $event->expects($this->once())
              ->method('getSourceIp')
              ->willReturn('127.0.0.1');

        $microtime->expects($this->once())
                  ->willReturn(1617733986.080936);

        $time->expects($this->once())
             ->willReturn(1617733986);

        $request = FastCgiRequest::createFromInvocationEvent($event, '/tmp/foo.php');

        $this->assertSame('foo', $request->getContent());
        $this->assertSame([
            'CONTENT_LENGTH' => 3,
            'DOCUMENT_ROOT' => '/tmp',
            'GATEWAY_INTERFACE' => 'FastCGI/1.0',
            'HTTP_HOST' => 'test.local',
            'HTTP_X_FORWARDED_HOST' => 'test.local',
            'PATH_INFO' => '',
            'PHP_SELF' => '/foo.php',
            'QUERY_STRING' => '',
            'REMOTE_ADDR' => '127.0.0.1',
            'REMOTE_PORT' => 80,
            'REQUEST_METHOD' => 'GET',
            'REQUEST_TIME' => 1617733986,
            'REQUEST_TIME_FLOAT' => 1617733986.080936,
            'REQUEST_URI' => '/',
            'SCRIPT_FILENAME' => '/tmp/foo.php',
            'SCRIPT_NAME' => '/foo.php',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_NAME' => 'test.local',
            'SERVER_PORT' => 80,
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_SOFTWARE' => 'ymir',
        ], $request->getParams());
    }

    public function testCreateFromInvocationEventWithXForwardedProto(): void
    {
        $event = $this->getHttpRequestEventMock();
        $getcwd = $this->getFunctionMock($this->getNamespace(FastCgiRequest::class), 'getcwd');
        $microtime = $this->getFunctionMock($this->getNamespace(FastCgiRequest::class), 'microtime');
        $time = $this->getFunctionMock($this->getNamespace(FastCgiRequest::class), 'time');

        $getcwd->expects($this->once())
               ->willReturn('/tmp');

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

        $event->expects($this->once())
              ->method('getSourceIp')
              ->willReturn('127.0.0.1');

        $microtime->expects($this->once())
                  ->willReturn(1617733986.080936);

        $time->expects($this->once())
             ->willReturn(1617733986);

        $request = FastCgiRequest::createFromInvocationEvent($event, '/tmp/foo.php');

        $this->assertSame('foo', $request->getContent());
        $this->assertSame([
            'CONTENT_LENGTH' => 3,
            'DOCUMENT_ROOT' => '/tmp',
            'GATEWAY_INTERFACE' => 'FastCGI/1.0',
            'HTTPS' => 'on',
            'HTTP_HOST' => 'localhost',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'PATH_INFO' => '',
            'PHP_SELF' => '/foo.php',
            'QUERY_STRING' => '',
            'REMOTE_ADDR' => '127.0.0.1',
            'REMOTE_PORT' => 80,
            'REQUEST_METHOD' => 'GET',
            'REQUEST_TIME' => 1617733986,
            'REQUEST_TIME_FLOAT' => 1617733986.080936,
            'REQUEST_URI' => '/',
            'SCRIPT_FILENAME' => '/tmp/foo.php',
            'SCRIPT_NAME' => '/foo.php',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_SOFTWARE' => 'ymir',
        ], $request->getParams());
    }

    public function testGetAcceptableEncodingsWithAcceptEncodingHeader(): void
    {
        $this->assertSame(['deflate', 'gzip'], (new FastCgiRequest('', ['HTTP_ACCEPT_ENCODING' => 'Deflate, gzip']))->getAcceptableEncodings());
    }

    public function testGetAcceptableEncodingsWithoutAcceptEncodingHeader(): void
    {
        $this->assertEmpty((new FastCgiRequest())->getAcceptableEncodings());
    }

    public function testGetContent(): void
    {
        $request = new FastCgiRequest('foo', []);

        $this->assertSame('foo', $request->getContent());
    }

    public function testGetContentLengthCastsValue(): void
    {
        $request = new FastCgiRequest('', ['content_length' => '1']);

        $this->assertSame(1, $request->getContentLength());
    }

    public function testGetContentLengthDefaultValue(): void
    {
        $this->assertSame(0, (new FastCgiRequest())->getContentLength());
    }

    public function testGetContentLengthWithValue(): void
    {
        $request = new FastCgiRequest('', ['content_length' => 42]);

        $this->assertSame(42, $request->getContentLength());
    }

    public function testGetContentTypeCastsValue(): void
    {
        $request = new FastCgiRequest('', ['content_type' => 1]);

        $this->assertSame('1', $request->getContentType());
    }

    public function testGetContentTypeDefaultValue(): void
    {
        $this->assertSame('application/x-www-form-urlencoded', (new FastCgiRequest())->getContentType());
    }

    public function testGetContentTypeWithValue(): void
    {
        $request = new FastCgiRequest('', ['content_type' => 'text/html']);

        $this->assertSame('text/html', $request->getContentType());
    }

    public function testGetCustomVars(): void
    {
        $this->assertSame(['FOO' => 'bar'], (new FastCgiRequest('', ['foo' => 'bar']))->getCustomVars());
    }

    public function testGetGatewayInterfaceCastsValue(): void
    {
        $request = new FastCgiRequest('', ['gateway_interface' => 1]);

        $this->assertSame('1', $request->getGatewayInterface());
    }

    public function testGetGatewayInterfaceDefaultValue(): void
    {
        $this->assertSame('FastCGI/1.0', (new FastCgiRequest())->getGatewayInterface());
    }

    public function testGetGatewayInterfaceWithValue(): void
    {
        $request = new FastCgiRequest('', ['gateway_interface' => 'FastCGI/2.0']);

        $this->assertSame('FastCGI/2.0', $request->getGatewayInterface());
    }

    public function testGetParams(): void
    {
        $this->assertSame(['FOO' => 'bar'], (new FastCgiRequest('', ['foo' => 'bar']))->getParams());
    }

    public function testGetPassThroughCallback(): void
    {
        $this->assertSame([], (new FastCgiRequest())->getPassThroughCallbacks());
    }

    public function testGetRemoteAddressCastsValue(): void
    {
        $request = new FastCgiRequest('', ['remote_addr' => 1]);

        $this->assertSame('1', $request->getRemoteAddress());
    }

    public function testGetRemoteAddressDefaultValue(): void
    {
        $this->assertSame('192.168.0.1', (new FastCgiRequest())->getRemoteAddress());
    }

    public function testGetRemoteAddressWithValue(): void
    {
        $request = new FastCgiRequest('', ['remote_addr' => '192.168.1.1']);

        $this->assertSame('192.168.1.1', $request->getRemoteAddress());
    }

    public function testGetRemotePortCastsValue(): void
    {
        $request = new FastCgiRequest('', ['remote_port' => '1']);

        $this->assertSame(1, $request->getRemotePort());
    }

    public function testGetRemotePortDefaultValue(): void
    {
        $this->assertSame(9985, (new FastCgiRequest())->getRemotePort());
    }

    public function testGetRemotePortWithValue(): void
    {
        $request = new FastCgiRequest('', ['remote_port' => 42]);

        $this->assertSame(42, $request->getRemotePort());
    }

    public function testGetRequestMethodCastsValue(): void
    {
        $request = new FastCgiRequest('', ['request_method' => 1]);

        $this->assertSame('1', $request->getRequestMethod());
    }

    public function testGetRequestMethodDefaultValue(): void
    {
        $this->assertSame('GET', (new FastCgiRequest())->getRequestMethod());
    }

    public function testGetRequestMethodWithValue(): void
    {
        $request = new FastCgiRequest('', ['request_method' => 'post']);

        $this->assertSame('POST', $request->getRequestMethod());
    }

    public function testGetRequestUriCastsValue(): void
    {
        $request = new FastCgiRequest('', ['request_uri' => 1]);

        $this->assertSame('1', $request->getRequestUri());
    }

    public function testGetRequestUriDefaultValue(): void
    {
        $this->assertSame('', (new FastCgiRequest())->getRequestUri());
    }

    public function testGetRequestUriWithValue(): void
    {
        $request = new FastCgiRequest('', ['request_uri' => '/']);

        $this->assertSame('/', $request->getRequestUri());
    }

    public function testGetResponseCallbacks(): void
    {
        $this->assertSame([], (new FastCgiRequest())->getResponseCallbacks());
    }

    public function testGetScriptFilenameCastsValue(): void
    {
        $request = new FastCgiRequest('', ['script_filename' => 1]);

        $this->assertSame('1', $request->getScriptFilename());
    }

    public function testGetScriptFilenameDefaultValue(): void
    {
        $this->assertSame('', (new FastCgiRequest())->getScriptFilename());
    }

    public function testGetScriptFilenameWithValue(): void
    {
        $request = new FastCgiRequest('', ['script_filename' => 'index.php']);

        $this->assertSame('index.php', $request->getScriptFilename());
    }

    public function testGetServerAddressCastsValue(): void
    {
        $request = new FastCgiRequest('', ['server_addr' => 1]);

        $this->assertSame('1', $request->getServerAddress());
    }

    public function testGetServerAddressDefaultValue(): void
    {
        $this->assertSame('127.0.0.1', (new FastCgiRequest())->getServerAddress());
    }

    public function testGetServerAddressWithValue(): void
    {
        $request = new FastCgiRequest('', ['server_addr' => '127.0.1.1']);

        $this->assertSame('127.0.1.1', $request->getServerAddress());
    }

    public function testGetServerNameCastsValue(): void
    {
        $request = new FastCgiRequest('', ['server_name' => 1]);

        $this->assertSame('1', $request->getServerName());
    }

    public function testGetServerNameDefaultValue(): void
    {
        $this->assertSame('localhost', (new FastCgiRequest())->getServerName());
    }

    public function testGetServerNameWithValue(): void
    {
        $request = new FastCgiRequest('', ['server_name' => 'ymir.local']);

        $this->assertSame('ymir.local', $request->getServerName());
    }

    public function testGetServerPortCastsValue(): void
    {
        $request = new FastCgiRequest('', ['server_port' => '1']);

        $this->assertSame(1, $request->getServerPort());
    }

    public function testGetServerPortDefaultValue(): void
    {
        $this->assertSame(80, (new FastCgiRequest())->getServerPort());
    }

    public function testGetServerPortWithValue(): void
    {
        $request = new FastCgiRequest('', ['server_port' => 22]);

        $this->assertSame(22, $request->getServerPort());
    }

    public function testGetServerProtocolCastsValue(): void
    {
        $request = new FastCgiRequest('', ['server_protocol' => 1]);

        $this->assertSame('1', $request->getServerProtocol());
    }

    public function testGetServerProtocolDefaultValue(): void
    {
        $this->assertSame('HTTP/1.1', (new FastCgiRequest())->getServerProtocol());
    }

    public function testGetServerProtocolWithValue(): void
    {
        $request = new FastCgiRequest('', ['server_protocol' => 'HTTP/1.0']);

        $this->assertSame('HTTP/1.0', $request->getServerProtocol());
    }

    public function testGetServerSoftwareCastsValue(): void
    {
        $request = new FastCgiRequest('', ['server_software' => 1]);

        $this->assertSame('1', $request->getServerSoftware());
    }

    public function testGetServerSoftwareDefaultValue(): void
    {
        $this->assertSame('ymir', (new FastCgiRequest())->getServerSoftware());
    }

    public function testGetServerSoftwareWithValue(): void
    {
        $request = new FastCgiRequest('', ['server_software' => 'foo']);

        $this->assertSame('foo', $request->getServerSoftware());
    }
}
