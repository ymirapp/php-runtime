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
use Ymir\Runtime\Lambda\Handler\Http\PhpScriptHttpEventHandler;
use Ymir\Runtime\Lambda\Response\Http\FastCgiHttpResponse;
use Ymir\Runtime\Tests\Mock\FunctionMockTrait;
use Ymir\Runtime\Tests\Mock\HttpRequestEventMockTrait;
use Ymir\Runtime\Tests\Mock\InvocationEventInterfaceMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;
use Ymir\Runtime\Tests\Mock\PhpFpmProcessMockTrait;

class PhpScriptHttpEventHandlerTest extends TestCase
{
    use FunctionMockTrait;
    use HttpRequestEventMockTrait;
    use InvocationEventInterfaceMockTrait;
    use LoggerMockTrait;
    use PhpFpmProcessMockTrait;

    public function testCanHandleNonExistentScriptFile(): void
    {
        $file_exists = $this->getFunctionMock($this->getNamespace(PhpScriptHttpEventHandler::class), 'file_exists');
        $process = $this->getPhpFpmProcessMock();

        $file_exists->expects($this->any())
                    ->willReturn(false);

        $handler = new PhpScriptHttpEventHandler($this->getLoggerMock(), $process, '/', 'tmp.php');

        $this->assertFalse($handler->canHandle($this->getHttpRequestEventMock()));
    }

    public function testCanHandleWithValidScriptFile(): void
    {
        $file_exists = $this->getFunctionMock($this->getNamespace(PhpScriptHttpEventHandler::class), 'file_exists');
        $process = $this->getPhpFpmProcessMock();

        $file_exists->expects($this->once())
                    ->with($this->identicalTo('/tmp.php'))
                    ->willReturn(true);

        $handler = new PhpScriptHttpEventHandler($this->getLoggerMock(), $process, '', 'tmp.php');

        $this->assertTrue($handler->canHandle($this->getHttpRequestEventMock()));
    }

    public function testCanHandleWrongEventType(): void
    {
        $file_exists = $this->getFunctionMock($this->getNamespace(PhpScriptHttpEventHandler::class), 'file_exists');
        $process = $this->getPhpFpmProcessMock();

        $file_exists->expects($this->any())
                    ->with($this->identicalTo('/tmp.php'))
                    ->willReturn(true);

        $handler = new PhpScriptHttpEventHandler($this->getLoggerMock(), $process, '/', 'tmp.php');

        $this->assertFalse($handler->canHandle($this->getInvocationEventInterfaceMock()));
    }

    public function testCanHandleWrongScriptFileType(): void
    {
        $process = $this->getPhpFpmProcessMock();

        $handler = new PhpScriptHttpEventHandler($this->getLoggerMock(), $process, '/', 'tmp');

        $this->assertFalse($handler->canHandle($this->getHttpRequestEventMock()));
    }

    public function testHandleCreatesFastCgiHttpResponseWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(PhpScriptHttpEventHandler::class), 'file_exists');
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->exactly(2))
              ->method('getPath')
              ->willReturn('tmp');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('1.0');

        $process->expects($this->once())
                ->method('handle')
                ->with($this->isInstanceOf(FastCgiRequest::class));

        $file_exists->expects($this->any())
                    ->willReturn(true);

        $handler = new PhpScriptHttpEventHandler($this->getLoggerMock(), $process, '/', 'tmp.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, $handler->handle($event));
    }

    public function testHandleCreatesFastCgiHttpResponseWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(PhpScriptHttpEventHandler::class), 'file_exists');
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->exactly(2))
              ->method('getPath')
              ->willReturn('tmp');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('2.0');

        $process->expects($this->once())
                ->method('handle')
                ->with($this->isInstanceOf(FastCgiRequest::class));

        $file_exists->expects($this->any())
                    ->willReturn(true);

        $handler = new PhpScriptHttpEventHandler($this->getLoggerMock(), $process, '/', 'tmp.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, $handler->handle($event));
    }
}
