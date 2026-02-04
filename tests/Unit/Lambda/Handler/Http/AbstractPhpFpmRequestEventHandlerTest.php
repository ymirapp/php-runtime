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

namespace Ymir\Runtime\Tests\Unit\Lambda\Handler\Http;

use PHPUnit\Framework\TestCase;
use Ymir\Runtime\FastCgi\FastCgiRequest;
use Ymir\Runtime\Lambda\Handler\Http\AbstractPhpFpmRequestEventHandler;
use Ymir\Runtime\Lambda\InvocationEvent\Context;
use Ymir\Runtime\Lambda\Response\Http\FastCgiHttpResponse;
use Ymir\Runtime\Tests\Mock\HttpRequestEventMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;
use Ymir\Runtime\Tests\Mock\PhpFpmProcessMockTrait;

class AbstractPhpFpmRequestEventHandlerTest extends TestCase
{
    use HttpRequestEventMockTrait;
    use LoggerMockTrait;
    use PhpFpmProcessMockTrait;

    public function testHandleCreatesCompressibleFastCgiHttpResponseWithGzipAcceptEncodingHeader(): void
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->once())
              ->method('getHeaders')
              ->willReturn(['accept-encoding' => ['deflate, gzip']]);

        $event->expects($this->exactly(2))
              ->method('getPath')
              ->willReturn('tmp');

        $event->expects($this->once())
              ->method('getContext')
              ->willReturn(new Context('request-id'));

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('1.0');

        $logger->expects($this->once())
               ->method('debug');

        $process->expects($this->once())
                ->method('handle')
                ->with($this->isInstanceOf(FastCgiRequest::class), $this->isType('int'));

        $handler = $this->getMockForAbstractClass(AbstractPhpFpmRequestEventHandler::class, [$logger, $process, '/']);

        $handler->expects($this->once())
                ->method('getScriptFilePath')
                ->with($this->identicalTo($event))
                ->willReturn('/path');

        $response = $handler->handle($event);

        $this->assertInstanceOf(FastCgiHttpResponse::class, $response);
        $this->assertTrue($response->isCompressible());
    }

    public function testHandleCreatesFastCgiHttpResponse(): void
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->exactly(2))
              ->method('getPath')
              ->willReturn('tmp');

        $event->expects($this->once())
              ->method('getContext')
              ->willReturn(new Context('request-id'));

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('1.0');

        $logger->expects($this->once())
               ->method('debug');

        $process->expects($this->once())
                ->method('handle')
                ->with($this->isInstanceOf(FastCgiRequest::class), $this->isType('int'));

        $handler = $this->getMockForAbstractClass(AbstractPhpFpmRequestEventHandler::class, [$logger, $process, '/']);

        $handler->expects($this->once())
                ->method('getScriptFilePath')
                ->with($this->identicalTo($event))
                ->willReturn('/path');

        $response = $handler->handle($event);

        $this->assertInstanceOf(FastCgiHttpResponse::class, $response);
        $this->assertFalse($response->isCompressible());
    }

    public function testHandleDoesntReturnStaticFileHttpResponseForPhpFileWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file = tmpfile();
        $filePath = stream_get_meta_data($file)['uri'];
        $logger = $this->getLoggerMock();
        $phpFilePath = $filePath.'.php';
        $process = $this->getPhpFpmProcessMock();

        $logger->expects($this->once())
               ->method('debug');

        $handler = $this->getMockForAbstractClass(AbstractPhpFpmRequestEventHandler::class, [$logger, $process, '/']);

        rename($filePath, $phpFilePath);

        $event->expects($this->exactly(2))
              ->method('getPath')
              ->willReturn($phpFilePath);

        $event->expects($this->once())
              ->method('getContext')
              ->willReturn(new Context('request-id'));

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('1.0');

        $response = $handler->handle($event);

        $this->assertInstanceOf(FastCgiHttpResponse::class, $response);
        $this->assertFalse($response->isCompressible());
    }

    public function testHandleDoesntReturnStaticFileHttpResponseForPhpFileWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file = tmpfile();
        $filePath = stream_get_meta_data($file)['uri'];
        $logger = $this->getLoggerMock();
        $phpFilePath = $filePath.'.php';
        $process = $this->getPhpFpmProcessMock();

        $logger->expects($this->once())
               ->method('debug');

        $handler = $this->getMockForAbstractClass(AbstractPhpFpmRequestEventHandler::class, [$logger, $process, '/']);

        rename($filePath, $phpFilePath);

        $event->expects($this->exactly(2))
              ->method('getPath')
              ->willReturn($phpFilePath);

        $event->expects($this->once())
              ->method('getContext')
              ->willReturn(new Context('request-id'));

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('2.0');

        $response = $handler->handle($event);

        $this->assertInstanceOf(FastCgiHttpResponse::class, $response);
        $this->assertFalse($response->isCompressible());
    }
}
