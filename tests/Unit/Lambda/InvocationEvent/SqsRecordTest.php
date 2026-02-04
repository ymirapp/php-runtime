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

namespace Ymir\Runtime\Tests\Unit\Lambda\InvocationEvent;

use PHPUnit\Framework\TestCase;
use Ymir\Runtime\Lambda\InvocationEvent\SqsRecord;

class SqsRecordTest extends TestCase
{
    public function testGetBody(): void
    {
        $this->assertSame('foo', (new SqsRecord(['body' => 'foo']))->getBody());
    }

    public function testGetEventSourceArn(): void
    {
        $this->assertSame('arn:aws:sqs:us-east-1:123456789012:MyQueue', (new SqsRecord(['eventSourceARN' => 'arn:aws:sqs:us-east-1:123456789012:MyQueue']))->getEventSourceArn());
    }

    public function testGetMessageAttributes(): void
    {
        $this->assertSame(['foo' => 'bar'], (new SqsRecord(['messageAttributes' => ['foo' => 'bar']]))->getMessageAttributes());
    }

    public function testGetMessageId(): void
    {
        $this->assertSame('id', (new SqsRecord(['messageId' => 'id']))->getMessageId());
    }

    public function testGetReceiptHandle(): void
    {
        $this->assertSame('handle', (new SqsRecord(['receiptHandle' => 'handle']))->getReceiptHandle());
    }

    public function testJsonSerialize(): void
    {
        $record = ['foo' => 'bar', 'baz' => 'qux'];

        $this->assertSame($record, (new SqsRecord($record))->jsonSerialize());
    }

    public function testToArray(): void
    {
        $record = ['foo' => 'bar', 'baz' => 'qux'];

        $this->assertSame($record, (new SqsRecord($record))->toArray());
    }
}
