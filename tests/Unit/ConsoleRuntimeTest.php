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

namespace Ymir\Runtime\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ymir\Runtime\ConsoleRuntime;

/**
 * @covers \Ymir\Runtime\ConsoleRuntime
 */
class ConsoleRuntimeTest extends TestCase
{
    public function testType(): void
    {
        $this->assertSame('console', ConsoleRuntime::TYPE);
    }
}
