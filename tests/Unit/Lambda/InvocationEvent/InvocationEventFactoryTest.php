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

namespace Ymir\Runtime\Tests\Unit\Lambda\InvocationEvent;

use PHPUnit\Framework\TestCase;
use Ymir\Runtime\Exception\UnsupportedEventException;
use Ymir\Runtime\Lambda\InvocationEvent\ConsoleCommandEvent;
use Ymir\Runtime\Lambda\InvocationEvent\HttpRequestEvent;
use Ymir\Runtime\Lambda\InvocationEvent\InvocationEventFactory;
use Ymir\Runtime\Lambda\InvocationEvent\PhpConsoleCommandEvent;
use Ymir\Runtime\Lambda\InvocationEvent\PingEvent;
use Ymir\Runtime\Lambda\InvocationEvent\SqsEvent;
use Ymir\Runtime\Lambda\InvocationEvent\WarmUpEvent;
use Ymir\Runtime\Tests\Mock\InvocationContextMockTrait;

class InvocationEventFactoryTest extends TestCase
{
    use InvocationContextMockTrait;

    public function testCreateFromInvocationEventFails(): void
    {
        $this->expectException(UnsupportedEventException::class);
        $this->expectExceptionMessage('Unknown Lambda event type');

        InvocationEventFactory::createFromInvocationEvent($this->getInvocationContextMock(), []);
    }

    public function testCreatesConsoleCommandEvent(): void
    {
        $this->assertInstanceOf(ConsoleCommandEvent::class, InvocationEventFactory::createFromInvocationEvent($this->getInvocationContextMock(), ['command' => 'foo']));
    }

    public function testCreatesHttpRequestEventWithPayloadVersion1(): void
    {
        $this->assertInstanceOf(HttpRequestEvent::class, InvocationEventFactory::createFromInvocationEvent($this->getInvocationContextMock(), ['httpMethod' => 'get']));
    }

    public function testCreatesHttpRequestEventWithPayloadVersion2(): void
    {
        $this->assertInstanceOf(HttpRequestEvent::class, InvocationEventFactory::createFromInvocationEvent($this->getInvocationContextMock(), ['requestContext' => ['http' => ['method' => 'get']]]));
    }

    public function testCreatesPhpConsoleCommandEvent(): void
    {
        $this->assertInstanceOf(PhpConsoleCommandEvent::class, InvocationEventFactory::createFromInvocationEvent($this->getInvocationContextMock(), ['php' => 'foo']));
    }

    public function testCreatesPingEvent(): void
    {
        $this->assertInstanceOf(PingEvent::class, InvocationEventFactory::createFromInvocationEvent($this->getInvocationContextMock(), ['ping' => true]));
    }

    public function testCreatesSqsEvent(): void
    {
        $this->assertInstanceOf(SqsEvent::class, InvocationEventFactory::createFromInvocationEvent($this->getInvocationContextMock(), ['Records' => [['eventSource' => 'aws:sqs']]]));
    }

    public function testCreatesWarmUpEvent(): void
    {
        $this->assertInstanceOf(WarmUpEvent::class, InvocationEventFactory::createFromInvocationEvent($this->getInvocationContextMock(), ['warmup' => '5']));
    }
}
