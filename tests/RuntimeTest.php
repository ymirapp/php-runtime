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

namespace Ymir\Runtime\Tests;

use PHPUnit\Framework\TestCase;
use Ymir\Runtime\Runtime;
use Ymir\Runtime\Tests\Mock\LambdaEventHandlerInterfaceMockTrait;
use Ymir\Runtime\Tests\Mock\LambdaRuntimeApiClientMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;
use Ymir\Runtime\Tests\Mock\PhpFpmProcessMockTrait;

/**
 * @covers \Ymir\Runtime\Runtime
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
