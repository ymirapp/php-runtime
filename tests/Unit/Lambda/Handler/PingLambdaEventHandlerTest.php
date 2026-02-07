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
use Ymir\Runtime\Exception\InvalidHandlerEventException;
use Ymir\Runtime\Lambda\Handler\PingLambdaEventHandler;
use Ymir\Runtime\Lambda\Response\Http\HttpResponse;
use Ymir\Runtime\Tests\Mock\InvocationEventInterfaceMockTrait;
use Ymir\Runtime\Tests\Mock\PingEventMockTrait;

class PingLambdaEventHandlerTest extends TestCase
{
    use InvocationEventInterfaceMockTrait;
    use PingEventMockTrait;

    public function testCanHandlePingEventType(): void
    {
        $handler = new PingLambdaEventHandler();

        $this->assertTrue($handler->canHandle($this->getPingEventMock()));
    }

    public function testCanHandleWrongEventType(): void
    {
        $handler = new PingLambdaEventHandler();

        $this->assertFalse($handler->canHandle($this->getInvocationEventInterfaceMock()));
    }

    public function testHandleWithPingEvent(): void
    {
        $handler = new PingLambdaEventHandler();

        $this->assertInstanceOf(HttpResponse::class, $handler->handle($this->getPingEventMock()));
    }

    public function testHandleWithWrongEventType(): void
    {
        $this->expectException(InvalidHandlerEventException::class);
        $this->expectExceptionMessageMatches('/PingLambdaEventHandler cannot handle Mock_InvocationEventInterface[^\s]* event/');

        $handler = new PingLambdaEventHandler();

        $handler->handle($this->getInvocationEventInterfaceMock());
    }
}
