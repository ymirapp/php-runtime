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

namespace Ymir\Runtime\Lambda\InvocationEvent;

/**
 * An SQS record.
 */
class SqsRecord implements \JsonSerializable
{
    /**
     * The record details.
     *
     * @var array
     */
    private $record;

    /**
     * Constructor.
     */
    public function __construct(array $record)
    {
        $this->record = $record;
    }

    /**
     * Get the body of the message.
     */
    public function getBody(): string
    {
        return $this->record['body'];
    }

    /**
     * Get the event source ARN.
     */
    public function getEventSourceArn(): string
    {
        return $this->record['eventSourceARN'];
    }

    /**
     * Get the message attributes.
     */
    public function getMessageAttributes(): array
    {
        return $this->record['messageAttributes'];
    }

    /**
     * Get the unique ID of the message.
     */
    public function getMessageId(): string
    {
        return $this->record['messageId'];
    }

    /**
     * Get the receipt handle.
     */
    public function getReceiptHandle(): string
    {
        return $this->record['receiptHandle'];
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Convert the SQS record to an array.
     */
    public function toArray(): array
    {
        return $this->record;
    }
}
