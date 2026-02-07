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

namespace Ymir\Runtime\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ymir\Runtime\QueueRuntime;
use Ymir\Runtime\Tests\Mock\LambdaEventHandlerInterfaceMockTrait;
use Ymir\Runtime\Tests\Mock\LambdaRuntimeApiClientMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;

class QueueRuntimeTest extends TestCase
{
    use LambdaEventHandlerInterfaceMockTrait;
    use LambdaRuntimeApiClientMockTrait;
    use LoggerMockTrait;

    public function testCreateFromApplication(): void
    {
        $application = $this->getMockBuilder(\Ymir\Runtime\Application\ApplicationInterface::class)->getMock();
        $context = new \Ymir\Runtime\RuntimeContext($this->getLoggerMock(), $this->getLambdaRuntimeApiClientMock(), 'us-east-1', '/var/task');
        $handlers = $this->getMockBuilder(\Ymir\Runtime\Lambda\Handler\LambdaEventHandlerCollection::class)
                         ->disableOriginalConstructor()
                         ->getMock();

        $application->expects($this->once())
                    ->method('getContext')
                    ->willReturn($context);
        $application->expects($this->once())
                    ->method('getQueueHandlers')
                    ->willReturn($handlers);

        $this->assertInstanceOf(QueueRuntime::class, QueueRuntime::createFromApplication($application));
    }

    public function testType(): void
    {
        $this->assertSame('queue', QueueRuntime::TYPE);
    }
}
