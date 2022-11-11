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

    public function testGetHeadersAddsCookieHeaderWithPayloadVersion2()
    {
        $this->assertSame(['cookie' => ['cookie1; cookie2']], (new HttpRequestEvent('id', ['cookies' => ['cookie1', 'cookie2'], 'version' => '2.0']))->getHeaders());
    }

    public function testGetHeadersDefaultValue()
    {
        $this->assertSame([], (new HttpRequestEvent('id'))->getHeaders());
    }

    public function testGetHeadersDoesntAddCookieHeaderWithPayloadVersion1()
    {
        $this->assertEmpty((new HttpRequestEvent('id', ['cookies' => ['cookie1', 'cookie2']]))->getHeaders());
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

    public function testGetMethodWithPayloadVersion1Value()
    {
        $this->assertSame('POST', (new HttpRequestEvent('id', ['httpMethod' => 'post']))->getMethod());
    }

    public function testGetMethodWithPayloadVersion2Value()
    {
        $this->assertSame('POST', (new HttpRequestEvent('id', ['requestContext' => ['http' => ['method' => 'post']]]))->getMethod());
    }

    public function testGetPathCastsValue()
    {
        $this->assertSame('1', (new HttpRequestEvent('id', ['path' => 1]))->getPath());
    }

    public function testGetPathDefaultValue()
    {
        $this->assertSame('/', (new HttpRequestEvent('id'))->getPath());
    }

    public function testGetPathWithPayloadVersion1Value()
    {
        $this->assertSame('/path', (new HttpRequestEvent('id', ['path' => '/path']))->getPath());
    }

    public function testGetPathWithPayloadVersion2Value()
    {
        $this->assertSame('/path', (new HttpRequestEvent('id', ['rawPath' => '/path']))->getPath());
    }

    public function testGetPayloadVersionDefaultValue()
    {
        $this->assertSame('1.0', (new HttpRequestEvent('id'))->getPayloadVersion());
    }

    public function testGetPayloadVersionWithValue()
    {
        $this->assertSame('2.0', (new HttpRequestEvent('id', ['version' => '2.0']))->getPayloadVersion());
    }

    public function testGetProtocolCastsValue()
    {
        $this->assertSame('1', (new HttpRequestEvent('id', ['requestContext' => ['protocol' => 1]]))->getProtocol());
    }

    public function testGetProtocolDefaultValue()
    {
        $this->assertSame('HTTP/1.1', (new HttpRequestEvent('id'))->getProtocol());
    }

    public function testGetProtocolWithPayloadVersion1Value()
    {
        $this->assertSame('HTTP/1.0', (new HttpRequestEvent('id', ['requestContext' => ['protocol' => 'HTTP/1.0']]))->getProtocol());
    }

    public function testGetProtocolWithPayloadVersion2Value()
    {
        $this->assertSame('HTTP/1.0', (new HttpRequestEvent('id', ['requestContext' => ['http' => ['protocol' => 'HTTP/1.0']]]))->getProtocol());
    }

    public function testGetQueryStringDefaultValue()
    {
        $this->assertSame('', (new HttpRequestEvent('id'))->getQueryString());
    }

    public function testGetQueryStringWithPayloadVersion1AndMultiValueQueryStringParameters()
    {
        $this->assertSame('foo%5B0%5D=bar&foo%5B1%5D=baz%25', (new HttpRequestEvent('id', ['multiValueQueryStringParameters' => ['foo[]' => ['bar', 'baz%']]]))->getQueryString());
    }

    public function testGetQueryStringWithPayloadVersion1AndMultiValueQueryStringParametersAndQueryStringParameter()
    {
        $this->assertSame('foo%5B0%5D=bar&foo%5B1%5D=baz', (new HttpRequestEvent('id', [
            'queryStringParameters' => ['foo[]' => 'bar'],
            'multiValueQueryStringParameters' => ['foo[]' => ['bar', 'baz']],
        ]))->getQueryString());
    }

    public function testGetQueryStringWithPayloadVersion1AndQueryStringParameters()
    {
        $this->assertSame('foo=bar', (new HttpRequestEvent('id', ['queryStringParameters' => ['foo' => 'bar']]))->getQueryString());
    }

    public function testGetQueryStringWithPayloadVersion2UsesRawQueryString()
    {
        $this->assertSame('foo%5B0%5D=bar&foo%5B1%5D=baz', (new HttpRequestEvent('id', ['rawQueryString' => 'foo[]=bar&foo[]=baz', 'version' => '2.0']))->getQueryString());
    }

    public function testGetSourceIpDefaultValue()
    {
        $this->assertSame('127.0.0.1', (new HttpRequestEvent('id'))->getSourceIp());
    }

    public function testGetSourceIpWithPayloadVersion1Value()
    {
        $this->assertSame('127.1.1.1', (new HttpRequestEvent('id', ['requestContext' => ['identity' => ['sourceIp' => '127.1.1.1']]]))->getSourceIp());
    }

    public function testGetSourceIpWithPayloadVersion2Value()
    {
        $this->assertSame('127.1.1.1', (new HttpRequestEvent('id', ['requestContext' => ['http' => ['sourceIp' => '127.1.1.1']]]))->getSourceIp());
    }
}
