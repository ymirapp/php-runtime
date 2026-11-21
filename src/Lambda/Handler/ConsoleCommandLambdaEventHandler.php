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

namespace Ymir\Runtime\Lambda\Handler;

use Symfony\Component\Process\Process;
use Ymir\Runtime\Exception\InvalidHandlerEventException;
use Ymir\Runtime\Lambda\InvocationEvent\ConsoleCommandEvent;
use Ymir\Runtime\Lambda\InvocationEvent\InvocationEventInterface;
use Ymir\Runtime\Lambda\Response\ProcessResponse;
use Ymir\Runtime\Lambda\Response\ResponseInterface;
use Ymir\Runtime\Logger;
use Ymir\Runtime\ProcessOutputLogger;

/**
 * Lambda invocation event handler for console commands.
 */
class ConsoleCommandLambdaEventHandler implements LambdaEventHandlerInterface
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
        return $event instanceof ConsoleCommandEvent;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(InvocationEventInterface $event): ResponseInterface
    {
        if (!$event instanceof ConsoleCommandEvent) {
            throw new InvalidHandlerEventException($this, $event);
        }

        $context = $event->getContext();
        $processOutput = new ProcessOutputLogger($this->logger);

        $process = Process::fromShellCommandline("{$event->getCommand()} 2>&1", null, ['AWS_REQUEST_ID' => $context->getRequestId()]);
        $process->setTimeout(max(1, $context->getRemainingTimeInMs() / 1000 - 1));

        try {
            $process->run(function ($type, $output) use ($processOutput): void {
                $processOutput->log($output);
            });
        } finally {
            $processOutput->flush();
        }

        return new ProcessResponse($process);
    }
}
