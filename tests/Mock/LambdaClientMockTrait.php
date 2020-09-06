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

use AsyncAws\Lambda\LambdaClient;
use PHPUnit\Framework\MockObject\MockObject;

trait LambdaClientMockTrait
{
    /**
     * Get a mock of a LambdaClient object.
     */
    private function getLambdaClientMock(): MockObject
    {
        return $this->getMockBuilder(LambdaClient::class)
                    ->disableOriginalConstructor()
                    ->getMock();
    }
}
