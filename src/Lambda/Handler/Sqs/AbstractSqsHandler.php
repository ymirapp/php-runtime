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

use Ymir\Runtime\Exception\InvalidHandlerEventException;
use Ymir\Runtime\Lambda\Handler\LambdaEventHandlerInterface;
use Ymir\Runtime\Lambda\InvocationEvent\InvocationContext;
use Ymir\Runtime\Lambda\InvocationEvent\InvocationEventInterface;
use Ymir\Runtime\Lambda\InvocationEvent\SqsEvent;
use Ymir\Runtime\Lambda\InvocationEvent\SqsRecord;
use Ymir\Runtime\Lambda\Response\ResponseInterface;
use Ymir\Runtime\Lambda\Response\SqsResponse;
use Ymir\Runtime\Logger;

/**
 * Base handler for SQS events.
 */
abstract class AbstractSqsHandler implements LambdaEventHandlerInterface
{
    /**
     * The logger that sends logs to CloudWatch.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Constructor.
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

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
            throw new InvalidHandlerEventException($this, $event);
        }

        $failedRecords = [];

        $event->getRecords()->each(function (SqsRecord $record) use ($event, &$failedRecords): void {
            try {
                $this->logger->debug(sprintf('Processing SQS message [%s]', $record->getMessageId()), [
                    'record' => $record->toArray(),
                ]);

                $this->processRecord($event->getContext(), $record);
            } catch (\Throwable $exception) {
                $this->logger->error(sprintf('Processing SQS message [%s] failed: %s', $record->getMessageId(), $exception->getMessage()), [
                    'record' => $record->toArray(),
                ]);

                $failedRecords[] = $record;
            }
        });

        return new SqsResponse($failedRecords);
    }

    /**
     * Process a SQS message record.
     */
    abstract protected function processRecord(InvocationContext $context, SqsRecord $record): void;
}
