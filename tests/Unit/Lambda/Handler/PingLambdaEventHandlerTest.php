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
use Ymir\Runtime\Lambda\Handler\PingLambdaEventHandler;
use Ymir\Runtime\Lambda\Response\HttpResponse;
use Ymir\Runtime\Tests\Mock\InvocationEventInterfaceMockTrait;
use Ymir\Runtime\Tests\Mock\PingEventMockTrait;

/**
 * @covers \Ymir\Runtime\Lambda\Handler\PingLambdaEventHandler
 */
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
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('PingLambdaEventHandler can only handle PingEvent objects');

        $handler = new PingLambdaEventHandler();

        $handler->handle($this->getInvocationEventInterfaceMock());
    }
}
