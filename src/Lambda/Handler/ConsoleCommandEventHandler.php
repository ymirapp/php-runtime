<?php

declare(strict_types=1);

/*
 * This file is part of Placeholder PHP Runtime.
 *
 * (c) Carl Alexander <contact@carlalexander.ca>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Placeholder\Runtime\Lambda\Handler;

use Placeholder\Runtime\Lambda\InvocationEvent\ConsoleCommandEvent;
use Placeholder\Runtime\Lambda\InvocationEvent\InvocationEventInterface;
use Placeholder\Runtime\Lambda\Response\ProcessResponse;
use Placeholder\Runtime\Lambda\Response\ResponseInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Lambda invocation event handler for console commands.
 */
class ConsoleCommandEventHandler implements LambdaEventHandlerInterface
{
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
            throw new \InvalidArgumentException(sprintf('"%s" can only handle console command events', self::class));
        }

        $process = Process::fromShellCommandline("{$event->getCommand()} 2>&1");
        $process->setTimeout(null);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return new ProcessResponse($process);
    }
}
