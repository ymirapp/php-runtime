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

namespace Ymir\Runtime\Tests\Unit\Lambda\InvocationEvent;

use PHPUnit\Framework\TestCase;
use Ymir\Runtime\Lambda\InvocationEvent\HttpRequestEvent;

/**
 * @covers \Ymir\Runtime\Lambda\InvocationEvent\HttpRequestEvent
 */
class HttpRequestEventTest extends TestCase
{
    public function testGetBodyCastsValue()
    {
        $this->assertSame('1', (new HttpRequestEvent('id', ['body' => 1]))->getBody());
    }

    public function testGetBodyDecodesValue()
    {
        $this->assertSame('foo', (new HttpRequestEvent('id', ['body' => base64_encode('foo'), 'isBase64Encoded' => true]))->getBody());
    }

    public function testGetBodyDefaultValue()
    {
        $this->assertSame('', (new HttpRequestEvent('id'))->getBody());
    }

    public function testGetBodyWithValue()
    {
        $this->assertSame('foo', (new HttpRequestEvent('id', ['body' => 'foo']))->getBody());
    }

    public function testGetHeadersDefaultValue()
    {
        $this->assertSame([], (new HttpRequestEvent('id'))->getHeaders());
    }

    public function testGetHeadersWithHeaders()
    {
        $this->assertSame(['foo' => ['bar']], (new HttpRequestEvent('id', ['headers' => ['Foo' => 'bar']]))->getHeaders());
    }

    public function testGetHeadersWithMultiValueHeaders()
    {
        $this->assertSame(['foo' => 'bar'], (new HttpRequestEvent('id', ['multiValueHeaders' => ['Foo' => 'bar']]))->getHeaders());
    }

    public function testGetMethodCastsValue()
    {
        $this->assertSame('1', (new HttpRequestEvent('id', ['httpMethod' => 1]))->getMethod());
    }

    public function testGetMethodDefaultValue()
    {
        $this->assertSame('GET', (new HttpRequestEvent('id'))->getMethod());
    }

    public function testGetMethodWithValue()
    {
        $this->assertSame('POST', (new HttpRequestEvent('id', ['httpMethod' => 'post']))->getMethod());
    }

    public function testGetPathCastsValue()
    {
        $this->assertSame('1', (new HttpRequestEvent('id', ['path' => 1]))->getPath());
    }

    public function testGetPathDefaultValue()
    {
        $this->assertSame('/', (new HttpRequestEvent('id'))->getPath());
    }

    public function testGetPathWithValue()
    {
        $this->assertSame('/path', (new HttpRequestEvent('id', ['path' => '/path']))->getPath());
    }

    public function testGetProtocolCastsValue()
    {
        $this->assertSame('1', (new HttpRequestEvent('id', ['requestContext' => ['protocol' => 1]]))->getProtocol());
    }

    public function testGetProtocolDefaultValue()
    {
        $this->assertSame('HTTP/1.1', (new HttpRequestEvent('id'))->getProtocol());
    }

    public function testGetProtocolWithValue()
    {
        $this->assertSame('HTTP/1.0', (new HttpRequestEvent('id', ['requestContext' => ['protocol' => 'HTTP/1.0']]))->getProtocol());
    }

    public function testGetQueryStringDefaultValue()
    {
        $this->assertSame('', (new HttpRequestEvent('id'))->getQueryString());
    }

    public function testGetQueryStringWithMultiValueQueryStringParameters()
    {
        $this->assertSame('foo%5B0%5D=bar&foo%5B1%5D=baz', (new HttpRequestEvent('id', ['multiValueQueryStringParameters' => ['foo' => ['bar', 'baz']]]))->getQueryString());
    }

    public function testGetQueryStringWithQueryStringParameters()
    {
        $this->assertSame('foo=bar', (new HttpRequestEvent('id', ['queryStringParameters' => ['foo' => 'bar']]))->getQueryString());
    }
}
