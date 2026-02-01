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
use Ymir\Runtime\Lambda\Response\Http\NotFoundHttpResponse;

class NotFoundHttpResponseTest extends TestCase
{
    public function testGetDataWhenTemplateFound(): void
    {
        $message = 'foo';
        $statusCode = 404;
        $templatesDirectory = __DIR__.'/../../../../../templates';

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
        ], (new NotFoundHttpResponse($message, $templatesDirectory))->getResponseData());
    }

    public function testGetResponseDataWhenTemplateNotFound(): void
    {
        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 404,
            'body' => base64_encode(''),
            'headers' => [
                'Content-Type' => 'text/html',
            ],
        ], (new NotFoundHttpResponse('foo'))->getResponseData());
    }
}
