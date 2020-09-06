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

namespace Ymir\Runtime\Tests\Mock;

use PHPUnit\Framework\MockObject\MockObject;
use Ymir\Runtime\Lambda\InvocationEvent\WarmUpEvent;

trait WarmUpEventMockTrait
{
    /**
     * Get a mock of a WarmUpEvent object.
     */
    private function getWarmUpEventMock(): MockObject
    {
        return $this->getMockBuilder(WarmUpEvent::class)
                    ->disableOriginalConstructor()
                    ->getMock();
    }
}
