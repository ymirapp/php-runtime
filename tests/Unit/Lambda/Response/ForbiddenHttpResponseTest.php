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
use Ymir\Runtime\Lambda\Response\ForbiddenHttpResponse;

/**
 * @covers \Ymir\Runtime\Lambda\Response\ForbiddenHttpResponse
 */
class ForbiddenHttpResponseTest extends TestCase
{
    public function testGetDataWhenTemplateFound()
    {
        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 403,
            'body' => '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',
            'headers' => [
                'Content-Type' => 'text/html',
                'Content-Encoding' => 'gzip',
                'Content-Length' => 1993,
            ],
        ], (new ForbiddenHttpResponse('foo', __DIR__.'/../../../../templates'))->getResponseData());
    }

    public function testGetResponseDataWhenTemplateNotFound()
    {
        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 403,
            'body' => 'H4sIAAAAAAACEwMAAAAAAAAAAAA=',
            'headers' => [
                'Content-Type' => 'text/html',
                'Content-Encoding' => 'gzip',
                'Content-Length' => 20,
            ],
        ], (new ForbiddenHttpResponse('foo'))->getResponseData());
    }
}
