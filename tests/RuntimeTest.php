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

namespace Placeholder\Runtime\Tests;

use PHPUnit\Framework\TestCase;
use Placeholder\Runtime\Runtime;
use Placeholder\Runtime\Tests\Mock\LambdaEventHandlerInterfaceMockTrait;
use Placeholder\Runtime\Tests\Mock\LambdaRuntimeApiClientMockTrait;
use Placeholder\Runtime\Tests\Mock\LoggerMockTrait;
use Placeholder\Runtime\Tests\Mock\PhpFpmProcessMockTrait;

/**
 * @covers \Placeholder\Runtime\Runtime
 */
class RuntimeTest extends TestCase
{
    use LambdaEventHandlerInterfaceMockTrait;
    use LambdaRuntimeApiClientMockTrait;
    use LoggerMockTrait;
    use PhpFpmProcessMockTrait;

    public function testStartWithNoException()
    {
        $client = $this->getLambdaRuntimeApiClientMock();
        $handler = $this->getLambdaEventHandlerInterfaceMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $process->expects($this->once())
                ->method('start');

        $runtime = new Runtime($client, $handler, $logger, $process);

        $runtime->start();
    }
}
