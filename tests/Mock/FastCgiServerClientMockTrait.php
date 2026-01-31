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
use Ymir\Runtime\FastCgi\FastCgiServerClient;

trait FastCgiServerClientMockTrait
{
    /**
     * Get a mock of a FastCgiServerClient object.
     */
    private function getFastCgiServerClientMock(): MockObject
    {
        return $this->getMockBuilder(FastCgiServerClient::class)
                    ->disableOriginalConstructor()
                    ->getMock();
    }
}
