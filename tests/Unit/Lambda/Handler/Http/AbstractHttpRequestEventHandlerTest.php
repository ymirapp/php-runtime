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
use Ymir\Runtime\Lambda\Handler\Http\AbstractHttpRequestEventHandler;
use Ymir\Runtime\Lambda\Response\Http\StaticFileResponse;
use Ymir\Runtime\Tests\Mock\HttpRequestEventMockTrait;
use Ymir\Runtime\Tests\Mock\HttpResponseMockTrait;
use Ymir\Runtime\Tests\Mock\InvocationEventInterfaceMockTrait;

class AbstractHttpRequestEventHandlerTest extends TestCase
{
    use HttpRequestEventMockTrait;
    use HttpResponseMockTrait;
    use InvocationEventInterfaceMockTrait;

    public function testCanHandleHttpRequestEventType(): void
    {
        $handler = $this->getMockForAbstractClass(AbstractHttpRequestEventHandler::class, ['/']);

        $this->assertTrue($handler->canHandle($this->getHttpRequestEventMock()));
    }

    public function testCanHandleWrongEventType(): void
    {
        $handler = $this->getMockForAbstractClass(AbstractHttpRequestEventHandler::class, ['/']);

        $this->assertFalse($handler->canHandle($this->getInvocationEventInterfaceMock()));
    }

    public function testHandleCallsCreateLambdaEventResponse(): void
    {
        $event = $this->getHttpRequestEventMock();
        $handler = $this->getMockForAbstractClass(AbstractHttpRequestEventHandler::class, ['/']);
        $response = $this->getHttpResponseMock();

        $event->expects($this->once())
              ->method('getPath')
              ->willReturn('tmp');

        $handler->expects($this->once())
                ->method('createLambdaEventResponse')
                ->with($this->identicalTo($event))
                ->willReturn($response);

        $this->assertSame($response, $handler->handle($event));
    }

    public function testHandleReturnsStaticFileResponse(): void
    {
        $event = $this->getHttpRequestEventMock();
        $tempDir = sys_get_temp_dir();

        $handler = $this->getMockForAbstractClass(AbstractHttpRequestEventHandler::class, [$tempDir]);

        touch($tempDir.'/foo');

        $event->expects($this->once())
              ->method('getPath')
              ->willReturn('/foo');

        $this->assertInstanceOf(StaticFileResponse::class, $handler->handle($event));

        @unlink($tempDir.'/foo');
    }

    public function testHandleWithWrongEventType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/[^\s]* cannot handle the given invocation event object/');

        $handler = $this->getMockForAbstractClass(AbstractHttpRequestEventHandler::class, ['/']);

        $handler->handle($this->getInvocationEventInterfaceMock());
    }
}
