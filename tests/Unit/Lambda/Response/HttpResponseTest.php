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
    public function testGetResponseDataWith304Status()
    {
        $response = new HttpResponse('foo', [], 304);

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 304,
        ], $response->getResponseData());
    }

    public function testGetResponseDataWithBody()
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

    public function testGetResponseDataWithContentTypeHeader()
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

    public function testGetResponseDataWithHeaders()
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
}
