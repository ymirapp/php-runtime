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
use Ymir\Runtime\Lambda\InvocationEvent\Context;
use Ymir\Runtime\Lambda\InvocationEvent\InvocationEventInterface;
use Ymir\Runtime\Lambda\InvocationEvent\SqsRecord;
use Ymir\Runtime\Logger;

class LaravelSqsHandler extends AbstractSqsHandler
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
     * {@inheritdoc}
     */
    public function canHandle(InvocationEventInterface $event): bool
    {
        return parent::canHandle($event)
            && file_exists($this->rootDirectory.'/public/index.php')
            && file_exists($this->rootDirectory.'/artisan');
    }

    /**
     * {@inheritdoc}
     */
    protected function processRecord(Context $context, SqsRecord $record): void
    {
        $message = json_encode($record);

        if (empty($message) || JSON_ERROR_NONE !== json_last_error()) {
            throw new \RuntimeException(sprintf('Failed to encode SQS message [%s]: %s', $record->getMessageId(), json_last_error_msg()));
        }

        $arguments = [
            '/opt/bin/php',
            $this->rootDirectory.'/artisan',
            'ymir:queue:work',
            sprintf('--connection=%s', $_ENV['YMIR_QUEUE_CONNECTION'] ?? $_ENV['QUEUE_CONNECTION'] ?? 'sqs'),
            sprintf('--message=%s', base64_encode($message)),
            sprintf('--delay=%s', $_ENV['YMIR_QUEUE_DELAY'] ?? $_ENV['SQS_DELAY'] ?? 0),
            sprintf('--timeout=%s', $this->calculateTimeout($context)),
            sprintf('--tries=%s', $_ENV['YMIR_QUEUE_TRIES'] ?? $_ENV['SQS_TRIES'] ?? 0),
        ];

        if ($_ENV['YMIR_QUEUE_FORCE'] ?? $_ENV['SQS_FORCE'] ?? false) {
            $arguments[] = '--force';
        }

        $process = new Process($arguments);
        $process->setTimeout(null);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(sprintf('Laravel queue job failed: %s', $process->getErrorOutput()));
        }
    }

    /**
     * Calculate the timeout for processing the SQS record.
     */
    private function calculateTimeout(Context $context): int
    {
        $timeout = (int) ($_ENV['YMIR_QUEUE_TIMEOUT'] ?? $_ENV['QUEUE_TIMEOUT'] ?? 0);
        $remainingTime = (int) max(0, $context->getRemainingTimeInMs() / 1000 - 1);

        return $timeout > 0 ? min($remainingTime, $timeout) : $remainingTime;
    }
}
