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
use Ymir\Runtime\Lambda\InvocationEvent\ConsoleCommandEvent;
use Ymir\Runtime\Lambda\InvocationEvent\InvocationEventInterface;
use Ymir\Runtime\Lambda\Response\ProcessResponse;
use Ymir\Runtime\Lambda\Response\ResponseInterface;
use Ymir\Runtime\Logger;

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
            throw new \InvalidArgumentException('ConsoleCommandLambdaEventHandler can only handle ConsoleCommandEvent objects');
        }

        $process = Process::fromShellCommandline("{$event->getCommand()} 2>&1");
        $process->setTimeout(max(1, $event->getContext()->getRemainingTimeInMs() / 1000 - 1));
        $process->run(function ($type, $output): void {
            $this->logger->info($output);
        });

        return new ProcessResponse($process);
    }
}
