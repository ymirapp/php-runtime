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
use Placeholder\Runtime\Lambda\LambdaRuntimeApiClient;

trait LambdaRuntimeApiClientMockTrait
{
    /**
     * Get a mock of a LambdaRuntimeApiClient object.
     */
    private function getLambdaRuntimeApiClientMock(): MockObject
    {
        return $this->getMockBuilder(LambdaRuntimeApiClient::class)
                    ->disableOriginalConstructor()
                    ->getMock();
    }
}
