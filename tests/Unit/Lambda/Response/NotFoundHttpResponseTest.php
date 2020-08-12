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
use Ymir\Runtime\Lambda\Response\NotFoundHttpResponse;

/**
 * @covers \Ymir\Runtime\Lambda\Response\NotFoundHttpResponse
 */
class NotFoundHttpResponseTest extends TestCase
{
    public function testGetDataWithBody()
    {
        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 404,
            'body' => 'Zm9v',
            'multiValueHeaders' => [
                'Content-Type' => ['text/html'],
            ],
        ], (new NotFoundHttpResponse('foo'))->getResponseData());
    }

    public function testGetDataWithNoBody()
    {
        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 404,
            'body' => '',
            'multiValueHeaders' => [
                'Content-Type' => ['text/html'],
            ],
        ], (new NotFoundHttpResponse())->getResponseData());
    }
}
