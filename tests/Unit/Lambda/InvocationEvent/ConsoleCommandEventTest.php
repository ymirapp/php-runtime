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
use Ymir\Runtime\Tests\Mock\InvocationContextMockTrait;

class ConsoleCommandEventTest extends TestCase
{
    use InvocationContextMockTrait;

    public function testGetCommand(): void
    {
        $this->assertSame('foo', (new ConsoleCommandEvent($this->getInvocationContextMock(), 'foo'))->getCommand());
    }
}
