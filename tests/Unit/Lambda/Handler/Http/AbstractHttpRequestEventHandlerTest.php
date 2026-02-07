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
use Ymir\Runtime\Exception\InvalidHandlerEventException;
use Ymir\Runtime\Lambda\Handler\Http\AbstractHttpRequestEventHandler;
use Ymir\Runtime\Lambda\Response\Http\StaticFileHttpResponse;
use Ymir\Runtime\Tests\Mock\FunctionMockTrait;
use Ymir\Runtime\Tests\Mock\HttpRequestEventMockTrait;
use Ymir\Runtime\Tests\Mock\HttpResponseMockTrait;
use Ymir\Runtime\Tests\Mock\InvocationEventInterfaceMockTrait;

class AbstractHttpRequestEventHandlerTest extends TestCase
{
    use FunctionMockTrait;
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
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
        $handler = $this->getMockForAbstractClass(AbstractHttpRequestEventHandler::class, ['/']);
        $response = $this->getHttpResponseMock();

        $event->expects($this->once())
              ->method('getPath')
              ->willReturn('tmp');

        $file_exists->expects($this->any())
                    ->willReturn(false);

        $is_dir->expects($this->any())
               ->willReturn(false);

        $handler->expects($this->once())
                ->method('createLambdaEventResponse')
                ->with($this->identicalTo($event))
                ->willReturn($response);

        $this->assertSame($response, $handler->handle($event));
    }

    public function testHandleReturnsStaticFileHttpResponse(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock('Ymir\Runtime\Lambda\Response\Http', 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');

        $handler = $this->getMockForAbstractClass(AbstractHttpRequestEventHandler::class, ['/tmp']);

        $event->expects($this->once())
              ->method('getPath')
              ->willReturn('/foo');

        $file_exists->expects($this->once())
                    ->with($this->identicalTo('/tmp/foo'))
                    ->willReturn(true);

        $is_dir->expects($this->once())
               ->with($this->identicalTo('/tmp/foo'))
               ->willReturn(false);

        $file_get_contents->expects($this->once())
                          ->with($this->identicalTo('/tmp/foo'))
                          ->willReturn('');

        $this->assertInstanceOf(StaticFileHttpResponse::class, $handler->handle($event));
    }

    public function testHandleWithWrongEventType(): void
    {
        $this->expectException(InvalidHandlerEventException::class);
        $this->expectExceptionMessageMatches('/[^\s]* cannot handle Mock_InvocationEventInterface[^\s]* event/');

        $handler = $this->getMockForAbstractClass(AbstractHttpRequestEventHandler::class, ['/']);

        $handler->handle($this->getInvocationEventInterfaceMock());
    }
}
