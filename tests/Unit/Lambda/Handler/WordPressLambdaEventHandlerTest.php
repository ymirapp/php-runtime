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
use Ymir\Runtime\Lambda\Handler\WordPressLambdaEventHandler;
use Ymir\Runtime\Tests\Mock\HttpRequestEventMockTrait;
use Ymir\Runtime\Tests\Mock\InvocationEventInterfaceMockTrait;
use Ymir\Runtime\Tests\Mock\PhpFpmProcessMockTrait;

/**
 * @covers \Ymir\Runtime\Lambda\Handler\WordPressLambdaEventHandler
 */
class WordPressLambdaEventHandlerTest extends TestCase
{
    use HttpRequestEventMockTrait;
    use InvocationEventInterfaceMockTrait;
    use PhpFpmProcessMockTrait;

    public function testCanHandleWithWithIndexAndWpConfigPresent()
    {
        $process = $this->getPhpFpmProcessMock();
        $tempDir = sys_get_temp_dir();

        $handler = new WordPressLambdaEventHandler($process, $tempDir);

        touch($tempDir.'/index.php');
        touch($tempDir.'/wp-config.php');

        $this->assertTrue($handler->canHandle($this->getHttpRequestEventMock()));

        unlink($tempDir.'/index.php');
        unlink($tempDir.'/wp-config.php');
    }

    public function testCanHandleWithWithMissingIndex()
    {
        $process = $this->getPhpFpmProcessMock();
        $tempDir = sys_get_temp_dir();

        $handler = new WordPressLambdaEventHandler($process, $tempDir);

        touch($tempDir.'/wp-config.php');

        $this->assertFalse($handler->canHandle($this->getHttpRequestEventMock()));

        unlink($tempDir.'/wp-config.php');
    }

    public function testCanHandleWithWithMissingWpConfig()
    {
        $process = $this->getPhpFpmProcessMock();
        $tempDir = sys_get_temp_dir();

        $handler = new WordPressLambdaEventHandler($process, $tempDir);

        touch($tempDir.'/index.php');

        $this->assertFalse($handler->canHandle($this->getHttpRequestEventMock()));

        unlink($tempDir.'/index.php');
    }

    public function testCanHandleWrongEventType()
    {
        $process = $this->getPhpFpmProcessMock();

        $handler = $handler = new WordPressLambdaEventHandler($process, '');

        $this->assertFalse($handler->canHandle($this->getInvocationEventInterfaceMock()));
    }

    public function testHandleCreatesFastCgiHttpResponse()
    {
        $event = $this->getHttpRequestEventMock();
        $process = $this->getPhpFpmProcessMock();
        $tempDir = sys_get_temp_dir();

        $event->expects($this->exactly(3))
              ->method('getPath')
              ->willReturn('tmp');

        $process->expects($this->once())
                ->method('handle')
                ->with($this->isInstanceOf(FastCgiRequest::class));

        $handler = new WordPressLambdaEventHandler($process, $tempDir);

        touch($tempDir.'/index.php');
        touch($tempDir.'/wp-config.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, $handler->handle($event));

        unlink($tempDir.'/index.php');
        unlink($tempDir.'/wp-config.php');
    }
}
