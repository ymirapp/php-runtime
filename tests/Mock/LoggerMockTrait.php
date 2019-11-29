<?php

declare(strict_types=1);

/*
 * This file is part of Placeholder PHP Runtime.
 *
 * (c) Carl Alexander <contact@carlalexander.ca>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Placeholder\Runtime\Tests\Mock;

use PHPUnit\Framework\MockObject\MockObject;
use Placeholder\Runtime\Logger;

trait LoggerMockTrait
{
    /**
     * Get a mock of a Logger object.
     */
    private function getLoggerMock(): MockObject
    {
        return $this->getMockBuilder(Logger::class)
                    ->disableOriginalConstructor()
                    ->getMock();
    }
}
