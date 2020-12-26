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
use Ymir\Runtime\Lambda\Handler\PhpScriptLambdaEventHandler;
use Ymir\Runtime\Tests\Mock\HttpRequestEventMockTrait;
use Ymir\Runtime\Tests\Mock\InvocationEventInterfaceMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;
use Ymir\Runtime\Tests\Mock\PhpFpmProcessMockTrait;

/**
 * @covers \Ymir\Runtime\Lambda\Handler\PhpScriptLambdaEventHandler
 */
class PhpScriptLambdaEventHandlerTest extends TestCase
{
    use HttpRequestEventMockTrait;
    use InvocationEventInterfaceMockTrait;
    use LoggerMockTrait;
    use PhpFpmProcessMockTrait;

    public function testCanHandleNonExistentScriptFile()
    {
        $process = $this->getPhpFpmProcessMock();

        $handler = new PhpScriptLambdaEventHandler($this->getLoggerMock(), $process, '/', '/tmp/tmp.php');

        $this->assertFalse($handler->canHandle($this->getHttpRequestEventMock()));
    }

    public function testCanHandleWithValidScriptFile()
    {
        $file = tmpfile();
        $filePath = stream_get_meta_data($file)['uri'];
        $phpFilePath = $filePath.'.php';
        $process = $this->getPhpFpmProcessMock();

        rename($filePath, $phpFilePath);

        $handler = new PhpScriptLambdaEventHandler($this->getLoggerMock(), $process, '', $phpFilePath);

        $this->assertTrue($handler->canHandle($this->getHttpRequestEventMock()));
    }

    public function testCanHandleWrongEventType()
    {
        $file = tmpfile();
        $filePath = stream_get_meta_data($file)['uri'];
        $phpFilePath = $filePath.'.php';
        $process = $this->getPhpFpmProcessMock();

        rename($filePath, $phpFilePath);

        $handler = new PhpScriptLambdaEventHandler($this->getLoggerMock(), $process, '/', $phpFilePath);

        $this->assertFalse($handler->canHandle($this->getInvocationEventInterfaceMock()));
    }

    public function testCanHandleWrongScriptFileType()
    {
        $process = $this->getPhpFpmProcessMock();

        $handler = new PhpScriptLambdaEventHandler($this->getLoggerMock(), $process, '/', '/tmp/tmp');

        $this->assertFalse($handler->canHandle($this->getHttpRequestEventMock()));
    }

    public function testHandleCreatesFastCgiHttpResponse()
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

        $process->expects($this->once())
                ->method('handle')
                ->with($this->isInstanceOf(FastCgiRequest::class));

        $handler = new PhpScriptLambdaEventHandler($this->getLoggerMock(), $process, '/', $phpFilePath);

        $this->assertInstanceOf(FastCgiHttpResponse::class, $handler->handle($event));
    }
}
