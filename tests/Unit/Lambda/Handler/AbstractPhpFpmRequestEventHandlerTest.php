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

namespace Ymir\Runtime\Tests\Unit\Lambda\Handler;

use PHPUnit\Framework\TestCase;
use Ymir\Runtime\FastCgi\FastCgiHttpResponse;
use Ymir\Runtime\FastCgi\FastCgiRequest;
use Ymir\Runtime\Lambda\Handler\AbstractPhpFpmRequestEventHandler;
use Ymir\Runtime\Tests\Mock\HttpRequestEventMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;
use Ymir\Runtime\Tests\Mock\PhpFpmProcessMockTrait;

/**
 * @covers \Ymir\Runtime\Lambda\Handler\AbstractPhpFpmRequestEventHandler
 */
class AbstractPhpFpmRequestEventHandlerTest extends TestCase
{
    use HttpRequestEventMockTrait;
    use LoggerMockTrait;
    use PhpFpmProcessMockTrait;

    public function testHandleCreatesFastCgiHttpResponse()
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->exactly(2))
              ->method('getPath')
              ->willReturn('tmp');

        $logger->expects($this->once())
            ->method('debug');

        $process->expects($this->once())
                ->method('handle')
                ->with($this->isInstanceOf(FastCgiRequest::class));

        $handler = $this->getMockForAbstractClass(AbstractPhpFpmRequestEventHandler::class, [$logger, $process, '/']);

        $handler->expects($this->once())
                ->method('getScriptFilePath')
                ->with($this->identicalTo($event))
                ->willReturn('/path');

        $this->assertInstanceOf(FastCgiHttpResponse::class, $handler->handle($event));
    }

    public function testHandleDoesntReturnStaticFileResponseForPhpFile()
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

        $this->assertInstanceOf(FastCgiHttpResponse::class, $handler->handle($event));
    }
}
