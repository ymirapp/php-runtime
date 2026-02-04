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

namespace Ymir\Runtime\Tests\Unit\Lambda\Response\Http;

use PHPUnit\Framework\TestCase;
use Ymir\Runtime\Lambda\Response\Http\StaticFileHttpResponse;

class StaticFileHttpResponseTest extends TestCase
{
    public function testGetResponseDataWithCorrectFileExtensionMimeType(): void
    {
        $filePath = stream_get_meta_data(tmpfile())['uri'].'.png';

        file_put_contents($filePath, 'foo');

        $response = new StaticFileHttpResponse($filePath);

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'body' => 'Zm9v',
            'headers' => [
                'Content-Type' => 'image/png',
            ],
        ], $response->getResponseData());
    }

    public function testGetResponseDataWithFileWithNoExtension(): void
    {
        $filePath = stream_get_meta_data(tmpfile())['uri'];

        file_put_contents($filePath, 'foo');

        $response = new StaticFileHttpResponse($filePath);

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'body' => 'Zm9v',
            'headers' => [
                'Content-Type' => 'text/plain',
            ],
        ], $response->getResponseData());
    }
}
