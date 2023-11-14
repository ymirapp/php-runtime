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

namespace Ymir\Runtime\Tests\Unit\Lambda\Response;

use PHPUnit\Framework\TestCase;
use Ymir\Runtime\Lambda\Response\HttpResponse;

/**
 * @covers \Ymir\Runtime\Lambda\Response\HttpResponse
 */
class HttpResponseTest extends TestCase
{
    public function testGetResponseDataWithFormatVersion1And304Status()
    {
        $response = new HttpResponse('foo', [], 304);

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 304,
        ], $response->getResponseData());
    }

    public function testGetResponseDataWithFormatVersion1AndBody()
    {
        $body = 'foo';
        $response = new HttpResponse($body);

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'body' => base64_encode($body),
            'multiValueHeaders' => [
                'Content-Type' => ['text/html'],
            ],
        ], $response->getResponseData());
    }

    public function testGetResponseDataWithFormatVersion1AndContentTypeHeader()
    {
        $response = new HttpResponse('foo', ['content-type' => 'bar']);

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'body' => 'Zm9v',
            'multiValueHeaders' => [
                'Content-Type' => ['bar'],
            ],
        ], $response->getResponseData());
    }

    public function testGetResponseDataWithFormatVersion1AndHeaders()
    {
        $body = 'foo';
        $response = new HttpResponse($body, ['foo' => 'bar']);

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'body' => base64_encode($body),
            'multiValueHeaders' => [
                'Foo' => ['bar'],
                'Content-Type' => ['text/html'],
            ],
        ], $response->getResponseData());
    }

    public function testGetResponseDataWithFormatVersion1AndHtmlCharsetContentTypeHeader()
    {
        $body = 'foo';
        $response = new HttpResponse($body, ['content-type' => 'text/html; charset=UTF-8']);

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'body' => base64_encode($body),
            'multiValueHeaders' => [
                'Content-Type' => ['text/html; charset=UTF-8'],
            ],
        ], $response->getResponseData());
    }

    public function testGetResponseDataWithFormatVersion1AndJsonContentTypeHeader()
    {
        $body = 'foo';
        $response = new HttpResponse($body, ['content-type' => 'application/json']);

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'body' => base64_encode($body),
            'multiValueHeaders' => [
                'Content-Type' => ['application/json'],
            ],
        ], $response->getResponseData());
    }

    public function testGetResponseDataWithFormatVersion1DoesntGzipEncodeIfCompressibleIsFalse()
    {
        $body = str_repeat('yep', 1900000);
        $response = new HttpResponse($body, [], 200, '1.0', false);

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'body' => base64_encode($body),
            'multiValueHeaders' => [
                'Content-Type' => ['text/html'],
            ],
        ], $response->getResponseData());
    }

    public function testGetResponseDataWithFormatVersion1DoesntGzipEncodeIfContentEncodingHeaderPresent()
    {
        $body = str_repeat('yep', 1900000);
        $response = new HttpResponse($body, ['content-encoding' => 'foo']);

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'body' => base64_encode($body),
            'multiValueHeaders' => [
                'Content-Encoding' => ['foo'],
                'Content-Type' => ['text/html'],
            ],
        ], $response->getResponseData());
    }

    public function testGetResponseDataWithFormatVersion1GzipEncodesResponseForHtmlContentType()
    {
        $body = str_repeat('yep', 1900000);
        $response = new HttpResponse($body, [], 200, '1.0');

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'body' => base64_encode(gzencode($body, 9)),
            'multiValueHeaders' => [
                'Content-Type' => ['text/html'],
                'Content-Encoding' => ['gzip'],
                'Content-Length' => [5560],
            ],
        ], $response->getResponseData());
    }

    public function testGetResponseDataWithFormatVersion1GzipEncodesResponseForJsonContentType()
    {
        $body = str_repeat('yep', 1900000);
        $response = new HttpResponse($body, ['content-type' => 'application/json'], 200, '1.0');

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'body' => base64_encode(gzencode($body, 9)),
            'multiValueHeaders' => [
                'Content-Type' => ['application/json'],
                'Content-Encoding' => ['gzip'],
                'Content-Length' => [5560],
            ],
        ], $response->getResponseData());
    }

    public function testGetResponseDataWithFormatVersion2And304Status()
    {
        $response = new HttpResponse('foo', [], 304, '2.0');

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 304,
        ], $response->getResponseData());
    }

    public function testGetResponseDataWithFormatVersion2AndBody()
    {
        $body = 'foo';
        $response = new HttpResponse($body, [], 200, '2.0');

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'body' => base64_encode($body),
            'headers' => [
                'Content-Type' => 'text/html',
            ],
        ], $response->getResponseData());
    }

    public function testGetResponseDataWithFormatVersion2AndContentTypeHeader()
    {
        $response = new HttpResponse('foo', ['content-type' => 'bar'], 200, '2.0');

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'body' => 'Zm9v',
            'headers' => [
                'Content-Type' => 'bar',
            ],
        ], $response->getResponseData());
    }

    public function testGetResponseDataWithFormatVersion2AndHeaders()
    {
        $body = 'foo';
        $response = new HttpResponse($body, ['foo' => 'bar'], 200, '2.0');

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'body' => base64_encode($body),
            'headers' => [
                'Foo' => 'bar',
                'Content-Type' => 'text/html',
            ],
        ], $response->getResponseData());
    }

    public function testGetResponseDataWithFormatVersion2AndHtmlCharsetContentTypeHeader()
    {
        $body = 'foo';
        $response = new HttpResponse($body, ['content-type' => 'text/html; charset=UTF-8'], 200, '2.0');

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'body' => base64_encode($body),
            'headers' => [
                'Content-Type' => 'text/html; charset=UTF-8',
            ],
        ], $response->getResponseData());
    }

    public function testGetResponseDataWithFormatVersion2AndJsonContentTypeHeader()
    {
        $body = 'foo';
        $response = new HttpResponse($body, ['content-type' => 'application/json'], 200, '2.0');

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'body' => base64_encode($body),
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ], $response->getResponseData());
    }

    public function testGetResponseDataWithFormatVersion2AndSetCookieHeaders()
    {
        $body = 'foo';
        $response = new HttpResponse($body, ['set-cookie' => ['foo', 'bar']], 200, '2.0');

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'cookies' => ['foo', 'bar'],
            'body' => base64_encode($body),
            'headers' => [
                'Content-Type' => 'text/html',
            ],
        ], $response->getResponseData());
    }

    public function testGetResponseDataWithFormatVersion2DoesntGzipEncodeIfCompressibleIsFalse()
    {
        $body = str_repeat('yep', 1900000);
        $response = new HttpResponse($body, [], 200, '2.0', false);

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'body' => base64_encode($body),
            'headers' => [
                'Content-Type' => 'text/html',
            ],
        ], $response->getResponseData());
    }

    public function testGetResponseDataWithFormatVersion2DoesntGzipEncodeIfContentEncodingHeaderPresent()
    {
        $body = str_repeat('yep', 1900000);
        $response = new HttpResponse($body, ['content-encoding' => 'foo'], 200, '2.0');

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'body' => base64_encode($body),
            'headers' => [
                'Content-Encoding' => 'foo',
                'Content-Type' => 'text/html',
            ],
        ], $response->getResponseData());
    }

    public function testGetResponseDataWithFormatVersion2GzipEncodesResponseForHtmlContentType()
    {
        $body = str_repeat('yep', 1900000);
        $response = new HttpResponse($body, [], 200, '2.0');

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'body' => base64_encode(gzencode($body, 9)),
            'headers' => [
                'Content-Type' => 'text/html',
                'Content-Encoding' => 'gzip',
                'Content-Length' => 5560,
            ],
        ], $response->getResponseData());
    }

    public function testGetResponseDataWithFormatVersion2GzipEncodesResponseForJsonContentType()
    {
        $body = str_repeat('yep', 1900000);
        $response = new HttpResponse($body, ['content-type' => 'application/json'], 200, '2.0');

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'body' => base64_encode(gzencode($body, 9)),
            'headers' => [
                'Content-Type' => 'application/json',
                'Content-Encoding' => 'gzip',
                'Content-Length' => 5560,
            ],
        ], $response->getResponseData());
    }

    public function testIsCompressible()
    {
        $this->assertFalse((new HttpResponse('foo', [], 200, '1.0', false))->isCompressible());
    }
}
