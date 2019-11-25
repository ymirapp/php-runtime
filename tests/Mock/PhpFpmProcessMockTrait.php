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
use Placeholder\Runtime\FastCgi\PhpFpmProcess;

trait PhpFpmProcessMockTrait
{
    /**
     * Get a mock of a PhpFpmProcess object.
     */
    private function getPhpFpmProcessMock(): MockObject
    {
        return $this->getMockBuilder(PhpFpmProcess::class)
                    ->disableOriginalConstructor()
                    ->getMock();
    }
}
