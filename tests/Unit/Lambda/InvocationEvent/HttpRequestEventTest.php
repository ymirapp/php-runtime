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
use Ymir\Runtime\Tests\Mock\ContextMockTrait;

/**
 * @covers \Ymir\Runtime\Lambda\InvocationEvent\HttpRequestEvent
 */
class HttpRequestEventTest extends TestCase
{
    use ContextMockTrait;

    public function testGetBodyCastsValue(): void
    {
        $this->assertSame('1', (new HttpRequestEvent($this->getContextMock(), ['body' => 1]))->getBody());
    }

    public function testGetBodyDecodesValue(): void
    {
        $this->assertSame('foo', (new HttpRequestEvent($this->getContextMock(), ['body' => base64_encode('foo'), 'isBase64Encoded' => true]))->getBody());
    }

    public function testGetBodyDefaultValue(): void
    {
        $this->assertSame('', (new HttpRequestEvent($this->getContextMock()))->getBody());
    }

    public function testGetBodyWithValue(): void
    {
        $this->assertSame('foo', (new HttpRequestEvent($this->getContextMock(), ['body' => 'foo']))->getBody());
    }

    public function testGetHeadersAddsCookieHeaderWithPayloadVersion2(): void
    {
        $this->assertSame(['cookie' => ['cookie1; cookie2']], (new HttpRequestEvent($this->getContextMock(), ['cookies' => ['cookie1', 'cookie2'], 'version' => '2.0']))->getHeaders());
    }

    public function testGetHeadersDefaultValue(): void
    {
        $this->assertSame([], (new HttpRequestEvent($this->getContextMock()))->getHeaders());
    }

    public function testGetHeadersDoesntAddCookieHeaderWithPayloadVersion1(): void
    {
        $this->assertEmpty((new HttpRequestEvent($this->getContextMock(), ['cookies' => ['cookie1', 'cookie2']]))->getHeaders());
    }

    public function testGetHeadersWithHeaders(): void
    {
        $this->assertSame(['foo' => ['bar']], (new HttpRequestEvent($this->getContextMock(), ['headers' => ['Foo' => 'bar']]))->getHeaders());
    }

    public function testGetHeadersWithMultiValueHeaders(): void
    {
        $this->assertSame(['foo' => 'bar'], (new HttpRequestEvent($this->getContextMock(), ['multiValueHeaders' => ['Foo' => 'bar']]))->getHeaders());
    }

    public function testGetMethodCastsValue(): void
    {
        $this->assertSame('1', (new HttpRequestEvent($this->getContextMock(), ['httpMethod' => 1]))->getMethod());
    }

    public function testGetMethodDefaultValue(): void
    {
        $this->assertSame('GET', (new HttpRequestEvent($this->getContextMock()))->getMethod());
    }

    public function testGetMethodWithPayloadVersion1Value(): void
    {
        $this->assertSame('POST', (new HttpRequestEvent($this->getContextMock(), ['httpMethod' => 'post']))->getMethod());
    }

    public function testGetMethodWithPayloadVersion2Value(): void
    {
        $this->assertSame('POST', (new HttpRequestEvent($this->getContextMock(), ['requestContext' => ['http' => ['method' => 'post']]]))->getMethod());
    }

    public function testGetPathCastsValue(): void
    {
        $this->assertSame('1', (new HttpRequestEvent($this->getContextMock(), ['path' => 1]))->getPath());
    }

    public function testGetPathDefaultValue(): void
    {
        $this->assertSame('/', (new HttpRequestEvent($this->getContextMock()))->getPath());
    }

    public function testGetPathWithPayloadVersion1Value(): void
    {
        $this->assertSame('/path', (new HttpRequestEvent($this->getContextMock(), ['path' => '/path']))->getPath());
    }

    public function testGetPathWithPayloadVersion2Value(): void
    {
        $this->assertSame('/path', (new HttpRequestEvent($this->getContextMock(), ['rawPath' => '/path']))->getPath());
    }

    public function testGetPayloadVersionDefaultValue(): void
    {
        $this->assertSame('1.0', (new HttpRequestEvent($this->getContextMock()))->getPayloadVersion());
    }

    public function testGetPayloadVersionWithValue(): void
    {
        $this->assertSame('2.0', (new HttpRequestEvent($this->getContextMock(), ['version' => '2.0']))->getPayloadVersion());
    }

    public function testGetProtocolCastsValue(): void
    {
        $this->assertSame('1', (new HttpRequestEvent($this->getContextMock(), ['requestContext' => ['protocol' => 1]]))->getProtocol());
    }

    public function testGetProtocolDefaultValue(): void
    {
        $this->assertSame('HTTP/1.1', (new HttpRequestEvent($this->getContextMock()))->getProtocol());
    }

    public function testGetProtocolWithPayloadVersion1Value(): void
    {
        $this->assertSame('HTTP/1.0', (new HttpRequestEvent($this->getContextMock(), ['requestContext' => ['protocol' => 'HTTP/1.0']]))->getProtocol());
    }

    public function testGetProtocolWithPayloadVersion2Value(): void
    {
        $this->assertSame('HTTP/1.0', (new HttpRequestEvent($this->getContextMock(), ['requestContext' => ['http' => ['protocol' => 'HTTP/1.0']]]))->getProtocol());
    }

    public function testGetQueryStringDefaultValue(): void
    {
        $this->assertSame('', (new HttpRequestEvent($this->getContextMock()))->getQueryString());
    }

    public function testGetQueryStringWithPayloadVersion1AndMultiValueQueryStringParameters(): void
    {
        $this->assertSame('foo%5B0%5D=bar&foo%5B1%5D=baz%25', (new HttpRequestEvent($this->getContextMock(), ['multiValueQueryStringParameters' => ['foo[]' => ['bar', 'baz%']]]))->getQueryString());
    }

    public function testGetQueryStringWithPayloadVersion1AndMultiValueQueryStringParametersAndQueryStringParameter(): void
    {
        $this->assertSame('foo%5B0%5D=bar&foo%5B1%5D=baz', (new HttpRequestEvent($this->getContextMock(), [
            'queryStringParameters' => ['foo[]' => 'bar'],
            'multiValueQueryStringParameters' => ['foo[]' => ['bar', 'baz']],
        ]))->getQueryString());
    }

    public function testGetQueryStringWithPayloadVersion1AndQueryStringParameters(): void
    {
        $this->assertSame('foo=bar', (new HttpRequestEvent($this->getContextMock(), ['queryStringParameters' => ['foo' => 'bar']]))->getQueryString());
    }

    public function testGetQueryStringWithPayloadVersion2UsesRawQueryString(): void
    {
        $this->assertSame('foo%5B0%5D=bar&foo%5B1%5D=baz', (new HttpRequestEvent($this->getContextMock(), ['rawQueryString' => 'foo[]=bar&foo[]=baz', 'version' => '2.0']))->getQueryString());
    }

    public function testGetSourceIpDefaultValue(): void
    {
        $this->assertSame('127.0.0.1', (new HttpRequestEvent($this->getContextMock()))->getSourceIp());
    }

    public function testGetSourceIpWithPayloadVersion1Value(): void
    {
        $this->assertSame('127.1.1.1', (new HttpRequestEvent($this->getContextMock(), ['requestContext' => ['identity' => ['sourceIp' => '127.1.1.1']]]))->getSourceIp());
    }

    public function testGetSourceIpWithPayloadVersion2Value(): void
    {
        $this->assertSame('127.1.1.1', (new HttpRequestEvent($this->getContextMock(), ['requestContext' => ['http' => ['sourceIp' => '127.1.1.1']]]))->getSourceIp());
    }
}
