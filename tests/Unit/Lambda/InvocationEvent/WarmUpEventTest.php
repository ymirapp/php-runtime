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
use Ymir\Runtime\Lambda\InvocationEvent\WarmUpEvent;
use Ymir\Runtime\Tests\Mock\InvocationContextMockTrait;

class WarmUpEventTest extends TestCase
{
    use InvocationContextMockTrait;

    public function testGetConcurrency(): void
    {
        $this->assertSame(5, (new WarmUpEvent($this->getInvocationContextMock(), 5))->getConcurrency());
    }
}
