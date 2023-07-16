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
        $message = 'foo';
        $statusCode = 403;
        $templatesDirectory = __DIR__.'/../../../../templates';

        ob_start();

        include $templatesDirectory.'/error.html.php';

        $body = (string) ob_get_clean();

        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => $statusCode,
            'body' => base64_encode($body),
            'headers' => [
                'Content-Type' => 'text/html',
            ],
        ], (new ForbiddenHttpResponse($message, $templatesDirectory))->getResponseData());
    }

    public function testGetResponseDataWhenTemplateNotFound()
    {
        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 403,
            'body' => base64_encode(''),
            'headers' => [
                'Content-Type' => 'text/html',
            ],
        ], (new ForbiddenHttpResponse('foo'))->getResponseData());
    }
}
