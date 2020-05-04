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
use Ymir\Runtime\FastCgi\FastCgiHttpResponse;
use Ymir\Runtime\Tests\Mock\ProvidesResponseDataMockTrait;

/**
 * @covers \Ymir\Runtime\FastCgi\FastCgiHttpResponse
 */
class FastCgiHttpResponseTest extends TestCase
{
    use ProvidesResponseDataMockTrait;

    public function testGetResponseDataWithDefaults()
    {
        $response = $this->getProvidesResponseDataMock();

        $response->expects($this->once())
                 ->method('getBody')
                 ->willReturn('');

        $response->expects($this->once())
                 ->method('getHeaders')
                 ->willReturn([]);

        $fastCgiResponse = new FastCgiHttpResponse($response);

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'body' => '',
            'multiValueHeaders' => [
                'Content-Type' => ['text/html'],
            ],
        ], $fastCgiResponse->getResponseData());
    }

    public function testGetResponseDataWithStatusHeader()
    {
        $response = $this->getProvidesResponseDataMock();

        $response->expects($this->once())
            ->method('getBody')
            ->willReturn('');

        $response->expects($this->once())
            ->method('getHeaders')
            ->willReturn(['Status' => ['201 Created']]);

        $fastCgiResponse = new FastCgiHttpResponse($response);

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 201,
            'body' => '',
            'multiValueHeaders' => [
                'Content-Type' => ['text/html'],
            ],
        ], $fastCgiResponse->getResponseData());
    }
}
