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
use Ymir\Runtime\Lambda\Response\Http\ServiceUnavailableHttpResponse;
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

    public static function provideEnabledMaintenanceModeValues(): iterable
    {
        yield ['true'];
        yield ['1'];
    }

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
        $getenv = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'getenv');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
        $handler = $this->getMockForAbstractClass(AbstractHttpRequestEventHandler::class, ['/']);
        $response = $this->getHttpResponseMock();

        $event->expects($this->once())
              ->method('getPath')
              ->willReturn('tmp');

        $file_exists->expects($this->any())
                    ->willReturn(false);

        $getenv->expects($this->once())
               ->with($this->identicalTo('YMIR_MAINTENANCE_MODE'))
               ->willReturn(false);

        $is_dir->expects($this->any())
               ->willReturn(false);

        $handler->expects($this->once())
                ->method('createLambdaEventResponse')
                ->with($this->identicalTo($event))
                ->willReturn($response);

        $this->assertSame($response, $handler->handle($event));
    }

    /**
     * @dataProvider provideEnabledMaintenanceModeValues
     */
    public function testHandleReturnsServiceUnavailableHttpResponseWhenMaintenanceModeEnabled(string $maintenanceMode): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $getenv = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'getenv');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
        $handler = $this->getMockBuilder(AbstractHttpRequestEventHandler::class)
                        ->setConstructorArgs(['/tmp'])
                        ->setMethods(['createLambdaEventResponse', 'isPubliclyAccessible'])
                        ->getMockForAbstractClass();

        $event->expects($this->once())
              ->method('getPath')
              ->willReturn('/foo');

        $file_exists->expects($this->never());

        $getenv->expects($this->once())
               ->with($this->identicalTo('YMIR_MAINTENANCE_MODE'))
               ->willReturn($maintenanceMode);

        $is_dir->expects($this->never());

        $handler->expects($this->never())
                ->method('createLambdaEventResponse');

        $handler->expects($this->never())
                ->method('isPubliclyAccessible');

        $this->assertInstanceOf(ServiceUnavailableHttpResponse::class, $handler->handle($event));
    }

    public function testHandleReturnsStaticFileHttpResponse(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock('Ymir\Runtime\Lambda\Response\Http', 'file_get_contents');
        $getenv = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'getenv');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');

        $handler = $this->getMockForAbstractClass(AbstractHttpRequestEventHandler::class, ['/tmp']);

        $event->expects($this->once())
              ->method('getPath')
              ->willReturn('/foo');

        $file_exists->expects($this->once())
                    ->with($this->identicalTo('/tmp/foo'))
                    ->willReturn(true);

        $getenv->expects($this->once())
               ->with($this->identicalTo('YMIR_MAINTENANCE_MODE'))
               ->willReturn(false);

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
