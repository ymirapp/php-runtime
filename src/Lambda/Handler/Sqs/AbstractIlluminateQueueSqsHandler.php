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

use Symfony\Component\Process\Process;
use Ymir\Runtime\Exception\SqsRecordProcessingException;
use Ymir\Runtime\Lambda\InvocationEvent\InvocationContext;
use Ymir\Runtime\Lambda\InvocationEvent\SqsRecord;
use Ymir\Runtime\Logger;

abstract class AbstractIlluminateQueueSqsHandler extends AbstractSqsHandler
{
    /**
     * The Lambda root directory.
     *
     * @var string
     */
    private $rootDirectory;

    /**
     * Constructor.
     */
    public function __construct(Logger $logger, string $rootDirectory)
    {
        parent::__construct($logger);

        $this->rootDirectory = rtrim($rootDirectory, '/');
    }

    /**
     * Get the Lambda root directory.
     */
    protected function getRootDirectory(): string
    {
        return $this->rootDirectory;
    }

    /**
     * {@inheritdoc}
     */
    final protected function processRecord(InvocationContext $context, SqsRecord $record): void
    {
        $message = json_encode($record);

        if (empty($message) || JSON_ERROR_NONE !== json_last_error()) {
            throw new SqsRecordProcessingException(sprintf('Failed to encode SQS message [%s]', $record->getMessageId()), json_last_error_msg());
        }

        $arguments = array_merge($this->getProcessArguments(), [
            sprintf('--connection=%s', $_ENV['YMIR_QUEUE_CONNECTION'] ?? $_ENV['QUEUE_CONNECTION'] ?? 'sqs'),
            sprintf('--message=%s', base64_encode($message)),
            sprintf('--delay=%s', $_ENV['YMIR_QUEUE_DELAY'] ?? $_ENV['SQS_DELAY'] ?? 0),
            sprintf('--timeout=%s', $this->calculateTimeout($context)),
            sprintf('--tries=%s', $_ENV['YMIR_QUEUE_TRIES'] ?? $_ENV['SQS_TRIES'] ?? 0),
        ]);

        if ($_ENV['YMIR_QUEUE_FORCE'] ?? $_ENV['SQS_FORCE'] ?? false) {
            $arguments[] = '--force';
        }

        $process = new Process($arguments);
        $process->setTimeout(null);
        $process->run(function (string $type, string $output): void {
            $this->writeProcessOutput($type, $output);
        });

        if ($process->isSuccessful()) {
            return;
        }

        $output = trim($process->getErrorOutput());

        if (empty($output)) {
            $output = trim($process->getOutput());
        }

        throw new SqsRecordProcessingException(sprintf('%s queue job failed', $this->getQueueName()), $output);
    }

    /**
     * Write the queue process output.
     */
    protected function writeProcessOutput(string $type, string $output): void
    {
        $stream = Process::ERR === $type ? STDERR : STDOUT;

        fwrite($stream, $output);
        fflush($stream);
    }

    /**
     * Get queue process arguments.
     *
     * @return string[]
     */
    abstract protected function getProcessArguments(): array;

    /**
     * Get queue implementation name.
     */
    abstract protected function getQueueName(): string;

    /**
     * Calculate the timeout for processing the SQS record.
     */
    private function calculateTimeout(InvocationContext $context): int
    {
        $timeout = (int) ($_ENV['YMIR_QUEUE_TIMEOUT'] ?? $_ENV['QUEUE_TIMEOUT'] ?? 0);
        $remainingTime = (int) max(0, $context->getRemainingTimeInMs() / 1000 - 1);

        return 0 < $timeout ? min($remainingTime, $timeout) : $remainingTime;
    }
}
