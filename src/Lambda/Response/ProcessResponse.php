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

namespace Placeholder\Runtime\Lambda\Response;

use Symfony\Component\Process\Process;

/**
 * A lambda response for a Symfony process.
 */
class ProcessResponse implements ResponseInterface
{
    /**
     * The process that we're generating a response for.
     *
     * @var Process
     */
    private $process;

    /**
     * Constructor.
     */
    public function __construct(Process $process)
    {
        $this->process = $process;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseData(): array
    {
        return [
            'exitCode' => $this->process->getExitCode(),
            'output' => $this->process->getOutput(),
        ];
    }
}
