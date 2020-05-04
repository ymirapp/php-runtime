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

namespace Ymir\Runtime\Tests\Unit\Lambda\Handler;

use PHPUnit\Framework\TestCase;
use Ymir\Runtime\Lambda\Handler\LambdaEventHandlerCollection;
use Ymir\Runtime\Tests\Mock\InvocationEventInterfaceMockTrait;
use Ymir\Runtime\Tests\Mock\LambdaEventHandlerInterfaceMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;
use Ymir\Runtime\Tests\Mock\ResponseInterfaceMockTrait;

/**
 * @covers \Ymir\Runtime\Lambda\Handler\LambdaEventHandlerCollection
 */
class LambdaEventHandlerCollectionTest extends TestCase
{
    use InvocationEventInterfaceMockTrait;
    use LambdaEventHandlerInterfaceMockTrait;
    use LoggerMockTrait;
    use ResponseInterfaceMockTrait;

    public function testCanHandleWithHandlerFound()
    {
        $event = $this->getInvocationEventInterfaceMock();
        $handler1 = $this->getLambdaEventHandlerInterfaceMock();
        $handler2 = $this->getLambdaEventHandlerInterfaceMock();
        $logger = $this->getLoggerMock();

        $handler1->expects($this->once())
                 ->method('canHandle')
                 ->with($this->identicalTo($event))
                 ->willReturn(false);

        $handler2->expects($this->once())
                 ->method('canHandle')
                 ->with($this->identicalTo($event))
                 ->willReturn(true);

        $collection = new LambdaEventHandlerCollection($logger, [$handler1, $handler2]);

        $this->assertTrue($collection->canHandle($event));
    }

    public function testCanHandleWithHandlerNotFound()
    {
        $event = $this->getInvocationEventInterfaceMock();
        $handler1 = $this->getLambdaEventHandlerInterfaceMock();
        $handler2 = $this->getLambdaEventHandlerInterfaceMock();
        $logger = $this->getLoggerMock();

        $handler1->expects($this->once())
                 ->method('canHandle')
                 ->with($this->identicalTo($event))
                 ->willReturn(false);

        $handler2->expects($this->once())
                 ->method('canHandle')
                 ->with($this->identicalTo($event))
                 ->willReturn(false);

        $collection = new LambdaEventHandlerCollection($logger, [$handler1, $handler2]);

        $this->assertFalse($collection->canHandle($event));
    }

    public function testHandleWithHandlerFound()
    {
        $event = $this->getInvocationEventInterfaceMock();
        $handler1 = $this->getLambdaEventHandlerInterfaceMock();
        $handler2 = $this->getLambdaEventHandlerInterfaceMock();
        $logger = $this->getLoggerMock();
        $response = $this->getResponseInterfaceMock();

        $handler1->expects($this->once())
                 ->method('canHandle')
                 ->with($this->identicalTo($event))
                 ->willReturn(false);

        $handler2->expects($this->once())
                 ->method('canHandle')
                 ->with($this->identicalTo($event))
                 ->willReturn(true);
        $handler2->expects($this->once())
                 ->method('handle')
                 ->with($this->identicalTo($event))
                 ->willReturn($response);

        $response->expects($this->once())
                 ->method('getResponseData')
                 ->willReturn([]);

        $logger->expects($this->exactly(2))
               ->method('info')
               ->withConsecutive(
                   [$this->matchesRegularExpression('/"[^"]*" handler selected for the event/')],
                   [$this->matchesRegularExpression('/"[^"]*" handler response:/'), $this->identicalTo([])]
               );

        $collection = new LambdaEventHandlerCollection($logger, [$handler1, $handler2]);

        $this->assertSame($response, $collection->handle($event));
    }

    public function testHandleWithHandlerNotFound()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No handler found to handle the event');

        $event = $this->getInvocationEventInterfaceMock();
        $handler1 = $this->getLambdaEventHandlerInterfaceMock();
        $handler2 = $this->getLambdaEventHandlerInterfaceMock();
        $logger = $this->getLoggerMock();

        $handler1->expects($this->once())
                 ->method('canHandle')
                 ->with($this->identicalTo($event))
                 ->willReturn(false);

        $handler2->expects($this->once())
                 ->method('canHandle')
                 ->with($this->identicalTo($event))
                 ->willReturn(false);

        $collection = new LambdaEventHandlerCollection($logger, [$handler1, $handler2]);

        $collection->handle($event);
    }
}
