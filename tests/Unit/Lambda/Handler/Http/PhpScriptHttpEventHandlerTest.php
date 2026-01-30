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
use Ymir\Runtime\FastCgi\FastCgiHttpResponse;
use Ymir\Runtime\FastCgi\FastCgiRequest;
use Ymir\Runtime\Lambda\Handler\Http\PhpScriptHttpEventHandler;
use Ymir\Runtime\Tests\Mock\HttpRequestEventMockTrait;
use Ymir\Runtime\Tests\Mock\InvocationEventInterfaceMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;
use Ymir\Runtime\Tests\Mock\PhpFpmProcessMockTrait;

/**
 * @covers \Ymir\Runtime\Lambda\Handler\Http\PhpScriptHttpEventHandler
 */
class PhpScriptHttpEventHandlerTest extends TestCase
{
    use HttpRequestEventMockTrait;
    use InvocationEventInterfaceMockTrait;
    use LoggerMockTrait;
    use PhpFpmProcessMockTrait;

    public function testCanHandleNonExistentScriptFile()
    {
        $process = $this->getPhpFpmProcessMock();

        $handler = new PhpScriptHttpEventHandler($this->getLoggerMock(), $process, '/', '/tmp/tmp.php');

        $this->assertFalse($handler->canHandle($this->getHttpRequestEventMock()));
    }

    public function testCanHandleWithValidScriptFile()
    {
        $file = tmpfile();
        $filePath = stream_get_meta_data($file)['uri'];
        $phpFilePath = $filePath.'.php';
        $process = $this->getPhpFpmProcessMock();

        rename($filePath, $phpFilePath);

        $handler = new PhpScriptHttpEventHandler($this->getLoggerMock(), $process, '', $phpFilePath);

        $this->assertTrue($handler->canHandle($this->getHttpRequestEventMock()));
    }

    public function testCanHandleWrongEventType()
    {
        $file = tmpfile();
        $filePath = stream_get_meta_data($file)['uri'];
        $phpFilePath = $filePath.'.php';
        $process = $this->getPhpFpmProcessMock();

        rename($filePath, $phpFilePath);

        $handler = new PhpScriptHttpEventHandler($this->getLoggerMock(), $process, '/', $phpFilePath);

        $this->assertFalse($handler->canHandle($this->getInvocationEventInterfaceMock()));
    }

    public function testCanHandleWrongScriptFileType()
    {
        $process = $this->getPhpFpmProcessMock();

        $handler = new PhpScriptHttpEventHandler($this->getLoggerMock(), $process, '/', '/tmp/tmp');

        $this->assertFalse($handler->canHandle($this->getHttpRequestEventMock()));
    }

    public function testHandleCreatesFastCgiHttpResponseWithPayloadVersion1()
    {
        $event = $this->getHttpRequestEventMock();
        $file = tmpfile();
        $filePath = stream_get_meta_data($file)['uri'];
        $phpFilePath = $filePath.'.php';
        $process = $this->getPhpFpmProcessMock();

        rename($filePath, $phpFilePath);

        $event->expects($this->exactly(2))
              ->method('getPath')
              ->willReturn('tmp');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('1.0');

        $process->expects($this->once())
                ->method('handle')
                ->with($this->isInstanceOf(FastCgiRequest::class));

        $handler = new PhpScriptHttpEventHandler($this->getLoggerMock(), $process, '/', $phpFilePath);

        $this->assertInstanceOf(FastCgiHttpResponse::class, $handler->handle($event));
    }

    public function testHandleCreatesFastCgiHttpResponseWithPayloadVersion2()
    {
        $event = $this->getHttpRequestEventMock();
        $file = tmpfile();
        $filePath = stream_get_meta_data($file)['uri'];
        $phpFilePath = $filePath.'.php';
        $process = $this->getPhpFpmProcessMock();

        rename($filePath, $phpFilePath);

        $event->expects($this->exactly(2))
              ->method('getPath')
              ->willReturn('tmp');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('2.0');

        $process->expects($this->once())
                ->method('handle')
                ->with($this->isInstanceOf(FastCgiRequest::class));

        $handler = new PhpScriptHttpEventHandler($this->getLoggerMock(), $process, '/', $phpFilePath);

        $this->assertInstanceOf(FastCgiHttpResponse::class, $handler->handle($event));
    }
}
