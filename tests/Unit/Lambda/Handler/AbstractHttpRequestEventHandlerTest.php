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
use Ymir\Runtime\Lambda\Handler\AbstractHttpRequestEventHandler;
use Ymir\Runtime\Lambda\Response\StaticFileResponse;
use Ymir\Runtime\Tests\Mock\HttpRequestEventMockTrait;
use Ymir\Runtime\Tests\Mock\HttpResponseMockTrait;
use Ymir\Runtime\Tests\Mock\InvocationEventInterfaceMockTrait;

/**
 * @covers \Ymir\Runtime\Lambda\Handler\AbstractHttpRequestEventHandler
 */
class AbstractHttpRequestEventHandlerTest extends TestCase
{
    use HttpRequestEventMockTrait;
    use HttpResponseMockTrait;
    use InvocationEventInterfaceMockTrait;

    public function testCanHandleHttpRequestEventType()
    {
        $handler = $this->getMockForAbstractClass(AbstractHttpRequestEventHandler::class, ['/']);

        $this->assertTrue($handler->canHandle($this->getHttpRequestEventMock()));
    }

    public function testCanHandleWrongEventType()
    {
        $handler = $this->getMockForAbstractClass(AbstractHttpRequestEventHandler::class, ['/']);

        $this->assertFalse($handler->canHandle($this->getInvocationEventInterfaceMock()));
    }

    public function testHandleCallsCreateLambdaEventResponse()
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

    public function testHandleReturnsStaticFileResponse()
    {
        $event = $this->getHttpRequestEventMock();
        $file = tmpfile();
        $handler = $this->getMockForAbstractClass(AbstractHttpRequestEventHandler::class, ['/']);

        $event->expects($this->once())
              ->method('getPath')
              ->willReturn(stream_get_meta_data($file)['uri']);

        $this->assertInstanceOf(StaticFileResponse::class, $handler->handle($event));
    }

    public function testHandleWithWrongEventType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/[^\s]* cannot handle the given invocation event object/');

        $handler = $this->getMockForAbstractClass(AbstractHttpRequestEventHandler::class, ['/']);

        $handler->handle($this->getInvocationEventInterfaceMock());
    }
}
