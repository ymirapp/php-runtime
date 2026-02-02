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

namespace Ymir\Runtime\Lambda\Handler\Sqs;

use Ymir\Runtime\Lambda\Handler\LambdaEventHandlerInterface;
use Ymir\Runtime\Lambda\InvocationEvent\InvocationEventInterface;
use Ymir\Runtime\Lambda\InvocationEvent\SqsEvent;
use Ymir\Runtime\Lambda\InvocationEvent\SqsRecord;
use Ymir\Runtime\Lambda\Response\ResponseInterface;
use Ymir\Runtime\Lambda\Response\SqsResponse;

/**
 * Base handler for SQS events.
 */
abstract class AbstractSqsHandler implements LambdaEventHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function canHandle(InvocationEventInterface $event): bool
    {
        return $event instanceof SqsEvent;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(InvocationEventInterface $event): ResponseInterface
    {
        if (!$event instanceof SqsEvent || !$this->canHandle($event)) {
            throw new \InvalidArgumentException(sprintf('%s cannot handle the given invocation event object', (new \ReflectionClass(static::class))->getShortName()));
        }

        $failedRecords = [];

        $event->getRecords()->each(function (SqsRecord $record) use (&$failedRecords): void {
            try {
                $this->processRecord($record);
            } catch (\Throwable $exception) {
                $failedRecords[] = $record;
            }
        });

        return new SqsResponse($failedRecords);
    }

    /**
     * Process a SQS message record.
     */
    abstract protected function processRecord(SqsRecord $record): void;
}
