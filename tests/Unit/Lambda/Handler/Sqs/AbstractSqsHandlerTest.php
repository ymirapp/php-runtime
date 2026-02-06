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

namespace Ymir\Runtime\Tests\Unit\Lambda\Handler\Sqs;

use PHPUnit\Framework\TestCase;
use Ymir\Runtime\Lambda\Handler\Sqs\AbstractSqsHandler;
use Ymir\Runtime\Lambda\InvocationEvent\InvocationContext;
use Ymir\Runtime\Lambda\InvocationEvent\SqsEvent;
use Ymir\Runtime\Lambda\InvocationEvent\SqsRecord;
use Ymir\Runtime\Lambda\Response\SqsResponse;
use Ymir\Runtime\Tests\Mock\InvocationContextMockTrait;
use Ymir\Runtime\Tests\Mock\InvocationEventInterfaceMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;

class AbstractSqsHandlerTest extends TestCase
{
    use InvocationContextMockTrait;
    use InvocationEventInterfaceMockTrait;
    use LoggerMockTrait;

    public function testCanHandleReturnsFalse(): void
    {
        $handler = $this->getMockForAbstractClass(AbstractSqsHandler::class, [$this->getLoggerMock()]);

        $this->assertFalse($handler->canHandle($this->getInvocationEventInterfaceMock()));
    }

    public function testCanHandleReturnsTrue(): void
    {
        $handler = $this->getMockForAbstractClass(AbstractSqsHandler::class, [$this->getLoggerMock()]);

        $context = $this->getInvocationContextMock();

        $this->assertTrue($handler->canHandle(new SqsEvent($context)));
    }

    public function testHandleCollectsFailures(): void
    {
        $record1 = new SqsRecord(['messageId' => 'id1']);
        $record2 = new SqsRecord(['messageId' => 'id2']);
        $record3 = new SqsRecord(['messageId' => 'id3']);

        $context = $this->getInvocationContextMock();

        $event = new SqsEvent($context, [
            'Records' => [
                ['messageId' => 'id1'],
                ['messageId' => 'id2'],
                ['messageId' => 'id3'],
            ],
        ]);

        $logger = $this->getLoggerMock();
        $logger->expects($this->exactly(3))
               ->method('debug')
               ->with($this->stringContains('Processing SQS message [id'));
        $logger->expects($this->once())
               ->method('error');

        $handler = $this->getMockForAbstractClass(AbstractSqsHandler::class, [$logger]);
        $handler->expects($this->exactly(3))
                ->method('processRecord')
                ->willReturnCallback(function (InvocationContext $context, SqsRecord $record): void {
                    if ('id2' === $record->getMessageId()) {
                        throw new \Exception('Failed');
                    }
                });

        $response = $handler->handle($event);

        $this->assertInstanceOf(SqsResponse::class, $response);
        $this->assertSame([
            'batchItemFailures' => [
                ['itemIdentifier' => 'id2'],
            ],
        ], $response->getResponseData());
    }
}
