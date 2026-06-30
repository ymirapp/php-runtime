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
use Ymir\Runtime\Lambda\InvocationEvent\PhpConsoleCommandEvent;
use Ymir\Runtime\Tests\Mock\InvocationContextMockTrait;

class PhpConsoleCommandEventTest extends TestCase
{
    use InvocationContextMockTrait;

    public static function provideCommands(): \Iterator
    {
        yield ['foo bar --baz', '/opt/bin/php foo bar --baz'];
        yield ['php foo bar --baz', '/opt/bin/php foo bar --baz'];
        yield ['  php foo bar --baz  ', '/opt/bin/php foo bar --baz'];
    }

    /**
     * @dataProvider provideCommands
     */
    public function testGetCommand(string $command, string $expectedCommand): void
    {
        $this->assertSame($expectedCommand, (new PhpConsoleCommandEvent($this->getInvocationContextMock(), $command))->getCommand());
    }
}
