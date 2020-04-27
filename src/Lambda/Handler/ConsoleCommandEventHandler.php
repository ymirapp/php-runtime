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

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Ymir\Runtime\Lambda\InvocationEvent\ConsoleCommandEvent;
use Ymir\Runtime\Lambda\InvocationEvent\InvocationEventInterface;
use Ymir\Runtime\Lambda\Response\ProcessResponse;
use Ymir\Runtime\Lambda\Response\ResponseInterface;

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
