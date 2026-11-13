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
use Ymir\Runtime\Tests\Mock\FunctionMockTrait;
use Ymir\Runtime\Tests\Mock\InvocationContextMockTrait;

class HttpRequestEventTest extends TestCase
{
    use FunctionMockTrait;
    use InvocationContextMockTrait;

    public static function provideForwardedHosts(): \Iterator
    {
        $domains = 'app.example.com,shop.example.net,*.example.org,site.ymirsites.com';

        yield 'mapped domain' => [$domains, ['app.example.com'], 'app.example.com'];
        yield 'alternate mapped domain' => [$domains, ['shop.example.net'], 'shop.example.net'];
        yield 'vanity domain' => [$domains, ['site.ymirsites.com'], 'site.ymirsites.com'];
        yield 'single-label wildcard' => [$domains, ['a.example.org'], 'a.example.org'];
        yield 'case-insensitive domain' => [strtoupper($domains), ['APP.Example.COM'], 'app.example.com'];
        yield 'HTTPS port' => [$domains, ['app.example.com:443'], 'app.example.com'];
        yield 'unmapped domain' => [$domains, ['attacker.example'], null];
        yield 'mapped name is not a prefix' => [$domains, ['app.example.com.attacker.example'], null];
        yield 'mapped name is not a suffix' => [$domains, ['evilapp.example.com'], null];
        yield 'wildcard excludes apex' => [$domains, ['example.org'], null];
        yield 'wildcard excludes deeper names' => [$domains, ['a.b.example.org'], null];
        yield 'wildcard is not a literal host' => [$domains, ['*.example.org'], null];
        yield 'wildcard label must be a hostname' => [$domains, ['-a.example.org'], null];
        yield 'non-HTTPS port' => [$domains, ['app.example.com:8443'], null];
        yield 'URL instead of hostname' => [$domains, ['https://app.example.com'], null];
        yield 'trailing newline' => [$domains, ["app.example.com\n"], null];
        yield 'comma-separated hosts' => [$domains, ['app.example.com, attacker.example'], null];
        yield 'repeated hosts' => [$domains, ['app.example.com', 'attacker.example'], null];
        yield 'missing mapped domains' => [null, ['app.example.com'], null];
        yield 'empty mapped domains' => ['', ['app.example.com'], null];
        yield 'no forwarded host' => [$domains, [], null];
    }

    public static function provideNonBooleanTrueHealthCheckValues(): \Iterator
    {
        yield 'array' => [[true]];
        yield 'false' => [false];
        yield 'integer' => [1];
        yield 'null' => [null];
        yield 'numeric string' => ['1'];
        yield 'string' => ['true'];
    }

    public function testGetBodyCastsValue(): void
    {
        $this->assertSame('1', (new HttpRequestEvent($this->getInvocationContextMock(), ['body' => 1]))->getBody());
    }

    public function testGetBodyDecodesValue(): void
    {
        $this->assertSame('foo', (new HttpRequestEvent($this->getInvocationContextMock(), ['body' => base64_encode('foo'), 'isBase64Encoded' => true]))->getBody());
    }

    public function testGetBodyDefaultValue(): void
    {
        $this->assertSame('', (new HttpRequestEvent($this->getInvocationContextMock()))->getBody());
    }

    public function testGetBodyWithValue(): void
    {
        $this->assertSame('foo', (new HttpRequestEvent($this->getInvocationContextMock(), ['body' => 'foo']))->getBody());
    }

    /**
     * @dataProvider provideForwardedHosts
     */
    public function testGetForwardedHostOnlyReturnsMappedDomains(?string $domains, array $forwardedHosts, ?string $expected): void
    {
        $this->getFunctionMock($this->getNamespace(HttpRequestEvent::class), 'getenv')
             ->expects($this->any())
             ->with('YMIR_DOMAIN_NAMES')
             ->willReturn($domains ?? false);

        $headers = empty($forwardedHosts) ? [] : ['X-Forwarded-Host' => $forwardedHosts];

        $this->assertSame($expected, (new HttpRequestEvent($this->getInvocationContextMock(), ['multiValueHeaders' => $headers]))->getForwardedHost());
        $this->assertSame($expected, (new HttpRequestEvent($this->getInvocationContextMock(), ['headers' => array_map(function (array $values): string {
            return implode(', ', $values);
        }, $headers), 'version' => '2.0']))->getForwardedHost());
    }

    public function testGetHeadersAddsCookieHeaderWithPayloadVersion2(): void
    {
        $this->assertSame(['cookie' => ['cookie1; cookie2']], (new HttpRequestEvent($this->getInvocationContextMock(), ['cookies' => ['cookie1', 'cookie2'], 'version' => '2.0']))->getHeaders());
    }

    public function testGetHeadersDefaultValue(): void
    {
        $this->assertSame([], (new HttpRequestEvent($this->getInvocationContextMock()))->getHeaders());
    }

    public function testGetHeadersDoesntAddCookieHeaderWithPayloadVersion1(): void
    {
        $this->assertEmpty((new HttpRequestEvent($this->getInvocationContextMock(), ['cookies' => ['cookie1', 'cookie2']]))->getHeaders());
    }

    public function testGetHeadersWithHeaders(): void
    {
        $this->assertSame(['foo' => ['bar']], (new HttpRequestEvent($this->getInvocationContextMock(), ['headers' => ['Foo' => 'bar']]))->getHeaders());
    }

    public function testGetHeadersWithMultiValueHeaders(): void
    {
        $this->assertSame(['foo' => 'bar'], (new HttpRequestEvent($this->getInvocationContextMock(), ['multiValueHeaders' => ['Foo' => 'bar']]))->getHeaders());
    }

    public function testGetMethodCastsValue(): void
    {
        $this->assertSame('1', (new HttpRequestEvent($this->getInvocationContextMock(), ['httpMethod' => 1]))->getMethod());
    }

    public function testGetMethodDefaultValue(): void
    {
        $this->assertSame('GET', (new HttpRequestEvent($this->getInvocationContextMock()))->getMethod());
    }

    public function testGetMethodWithPayloadVersion1Value(): void
    {
        $this->assertSame('POST', (new HttpRequestEvent($this->getInvocationContextMock(), ['httpMethod' => 'post']))->getMethod());
    }

    public function testGetMethodWithPayloadVersion2Value(): void
    {
        $this->assertSame('POST', (new HttpRequestEvent($this->getInvocationContextMock(), ['requestContext' => ['http' => ['method' => 'post']]]))->getMethod());
    }

    public function testGetPathCastsValue(): void
    {
        $this->assertSame('1', (new HttpRequestEvent($this->getInvocationContextMock(), ['path' => 1]))->getPath());
    }

    public function testGetPathDefaultValue(): void
    {
        $this->assertSame('/', (new HttpRequestEvent($this->getInvocationContextMock()))->getPath());
    }

    public function testGetPathWithPayloadVersion1Value(): void
    {
        $this->assertSame('/path', (new HttpRequestEvent($this->getInvocationContextMock(), ['path' => '/path']))->getPath());
    }

    public function testGetPathWithPayloadVersion2Value(): void
    {
        $this->assertSame('/path', (new HttpRequestEvent($this->getInvocationContextMock(), ['rawPath' => '/path']))->getPath());
    }

    public function testGetPayloadVersionDefaultValue(): void
    {
        $this->assertSame('1.0', (new HttpRequestEvent($this->getInvocationContextMock()))->getPayloadVersion());
    }

    public function testGetPayloadVersionWithValue(): void
    {
        $this->assertSame('2.0', (new HttpRequestEvent($this->getInvocationContextMock(), ['version' => '2.0']))->getPayloadVersion());
    }

    public function testGetProtocolCastsValue(): void
    {
        $this->assertSame('1', (new HttpRequestEvent($this->getInvocationContextMock(), ['requestContext' => ['protocol' => 1]]))->getProtocol());
    }

    public function testGetProtocolDefaultValue(): void
    {
        $this->assertSame('HTTP/1.1', (new HttpRequestEvent($this->getInvocationContextMock()))->getProtocol());
    }

    public function testGetProtocolWithPayloadVersion1Value(): void
    {
        $this->assertSame('HTTP/1.0', (new HttpRequestEvent($this->getInvocationContextMock(), ['requestContext' => ['protocol' => 'HTTP/1.0']]))->getProtocol());
    }

    public function testGetProtocolWithPayloadVersion2Value(): void
    {
        $this->assertSame('HTTP/1.0', (new HttpRequestEvent($this->getInvocationContextMock(), ['requestContext' => ['http' => ['protocol' => 'HTTP/1.0']]]))->getProtocol());
    }

    public function testGetQueryStringDefaultValue(): void
    {
        $this->assertSame('', (new HttpRequestEvent($this->getInvocationContextMock()))->getQueryString());
    }

    public function testGetQueryStringWithPayloadVersion1AndMultiValueQueryStringParameters(): void
    {
        $this->assertSame('foo%5B0%5D=bar&foo%5B1%5D=baz%25', (new HttpRequestEvent($this->getInvocationContextMock(), ['multiValueQueryStringParameters' => ['foo[]' => ['bar', 'baz%']]]))->getQueryString());
    }

    public function testGetQueryStringWithPayloadVersion1AndMultiValueQueryStringParametersAndQueryStringParameter(): void
    {
        $this->assertSame('foo%5B0%5D=bar&foo%5B1%5D=baz', (new HttpRequestEvent($this->getInvocationContextMock(), [
            'queryStringParameters' => ['foo[]' => 'bar'],
            'multiValueQueryStringParameters' => ['foo[]' => ['bar', 'baz']],
        ]))->getQueryString());
    }

    public function testGetQueryStringWithPayloadVersion1AndQueryStringParameters(): void
    {
        $this->assertSame('foo=bar', (new HttpRequestEvent($this->getInvocationContextMock(), ['queryStringParameters' => ['foo' => 'bar']]))->getQueryString());
    }

    public function testGetQueryStringWithPayloadVersion2UsesRawQueryString(): void
    {
        $this->assertSame('foo%5B0%5D=bar&foo%5B1%5D=baz', (new HttpRequestEvent($this->getInvocationContextMock(), ['rawQueryString' => 'foo[]=bar&foo[]=baz', 'version' => '2.0']))->getQueryString());
    }

    public function testGetSourceIpDefaultValue(): void
    {
        $this->assertSame('127.0.0.1', (new HttpRequestEvent($this->getInvocationContextMock()))->getSourceIp());
    }

    public function testGetSourceIpWithPayloadVersion1Value(): void
    {
        $this->assertSame('127.1.1.1', (new HttpRequestEvent($this->getInvocationContextMock(), ['requestContext' => ['identity' => ['sourceIp' => '127.1.1.1']]]))->getSourceIp());
    }

    public function testGetSourceIpWithPayloadVersion2Value(): void
    {
        $this->assertSame('127.1.1.1', (new HttpRequestEvent($this->getInvocationContextMock(), ['requestContext' => ['http' => ['sourceIp' => '127.1.1.1']]]))->getSourceIp());
    }

    public function testIsHealthCheckDefaultValue(): void
    {
        $this->assertFalse((new HttpRequestEvent($this->getInvocationContextMock()))->isHealthCheck());
    }

    public function testIsHealthCheckIgnoresRequestValues(): void
    {
        $this->assertFalse((new HttpRequestEvent($this->getInvocationContextMock(), [
            'body' => '{"ymirHealthCheck":true}',
            'headers' => ['YMIR_HEALTH_CHECK' => '1', 'ymir-health-check' => '1'],
            'rawQueryString' => 'ymirHealthCheck=true',
            'requestContext' => ['http' => ['method' => 'GET'], 'ymirHealthCheck' => true],
            'version' => '2.0',
        ]))->isHealthCheck());
    }

    /**
     * @dataProvider provideNonBooleanTrueHealthCheckValues
     */
    public function testIsHealthCheckWithNonBooleanTrueValue($value): void
    {
        $this->assertFalse((new HttpRequestEvent($this->getInvocationContextMock(), ['ymirHealthCheck' => $value]))->isHealthCheck());
    }

    public function testIsHealthCheckWithTrueValue(): void
    {
        $this->assertTrue((new HttpRequestEvent($this->getInvocationContextMock(), ['ymirHealthCheck' => true]))->isHealthCheck());
    }
}
