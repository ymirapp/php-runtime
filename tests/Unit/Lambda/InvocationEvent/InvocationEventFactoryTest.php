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
use Ymir\Runtime\Lambda\InvocationEvent\ConsoleCommandEvent;
use Ymir\Runtime\Lambda\InvocationEvent\HttpRequestEvent;
use Ymir\Runtime\Lambda\InvocationEvent\InvocationEventFactory;
use Ymir\Runtime\Lambda\InvocationEvent\PhpConsoleCommandEvent;
use Ymir\Runtime\Lambda\InvocationEvent\PingEvent;

/**
 * @covers \Ymir\Runtime\Lambda\InvocationEvent\InvocationEventFactory
 */
class InvocationEventFactoryTest extends TestCase
{
    public function testCreateFromInvocationEventFails()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown Lambda event type');

        InvocationEventFactory::createFromInvocationEvent('id', []);
    }

    public function testCreatesConsoleCommandEvent()
    {
        $this->assertInstanceOf(ConsoleCommandEvent::class, InvocationEventFactory::createFromInvocationEvent('id', ['command' => 'foo']));
    }

    public function testCreatesHttpRequestEvent()
    {
        $this->assertInstanceOf(HttpRequestEvent::class, InvocationEventFactory::createFromInvocationEvent('id', ['httpMethod' => 'get']));
    }

    public function testCreatesPhpConsoleCommandEvent()
    {
        $this->assertInstanceOf(PhpConsoleCommandEvent::class, InvocationEventFactory::createFromInvocationEvent('id', ['php' => 'foo']));
    }

    public function testCreatesPingEvent()
    {
        $this->assertInstanceOf(PingEvent::class, InvocationEventFactory::createFromInvocationEvent('id', ['ping' => true]));
    }
}
