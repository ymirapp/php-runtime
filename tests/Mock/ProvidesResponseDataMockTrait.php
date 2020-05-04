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

use hollodotme\FastCGI\Interfaces\ProvidesResponseData;
use PHPUnit\Framework\MockObject\MockObject;

trait ProvidesResponseDataMockTrait
{
    /**
     * Get a mock of a ProvidesResponseData object.
     */
    private function getProvidesResponseDataMock(): MockObject
    {
        return $this->getMockBuilder(ProvidesResponseData::class)
                    ->getMock();
    }
}
