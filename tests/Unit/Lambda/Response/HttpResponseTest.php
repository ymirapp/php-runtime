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
        $response = new HttpResponse('foo');

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'body' => 'Zm9v',
            'multiValueHeaders' => [
                'Content-Type' => ['text/html'],
            ],
        ], $response->getResponseData());
    }

    public function testGetResponseDataWithFormatVersion1AndContentTypeHeader()
    {
        $response = new HttpResponse('foo', ['content-type' => 'application/json']);

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'body' => 'Zm9v',
            'multiValueHeaders' => [
                'Content-Type' => ['application/json'],
            ],
        ], $response->getResponseData());
    }

    public function testGetResponseDataWithFormatVersion1AndHeaders()
    {
        $response = new HttpResponse('foo', ['foo' => 'bar']);

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'body' => 'Zm9v',
            'multiValueHeaders' => [
                'Foo' => ['bar'],
                'Content-Type' => ['text/html'],
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
        $response = new HttpResponse('foo', [], 200, '2.0');

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'body' => 'Zm9v',
            'headers' => [
                'Content-Type' => 'text/html',
            ],
        ], $response->getResponseData());
    }

    public function testGetResponseDataWithFormatVersion2AndContentTypeHeader()
    {
        $response = new HttpResponse('foo', ['content-type' => 'application/json'], 200, '2.0');

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'body' => 'Zm9v',
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ], $response->getResponseData());
    }

    public function testGetResponseDataWithFormatVersion2AndHeaders()
    {
        $response = new HttpResponse('foo', ['foo' => 'bar'], 200, '2.0');

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'body' => 'Zm9v',
            'headers' => [
                'Foo' => 'bar',
                'Content-Type' => 'text/html',
            ],
        ], $response->getResponseData());
    }

    public function testGetResponseDataWithFormatVersion2AndSetCookieHeaders()
    {
        $response = new HttpResponse('foo', ['set-cookie' => ['foo', 'bar']], 200, '2.0');

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'body' => 'Zm9v',
            'cookies' => ['foo', 'bar'],
            'headers' => [
                'Content-Type' => 'text/html',
            ],
        ], $response->getResponseData());
    }
}
