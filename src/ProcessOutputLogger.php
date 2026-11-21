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

namespace Ymir\Runtime;

/**
 * Logger for the output of a child process.
 */
class ProcessOutputLogger
{
    /**
     * Pattern matching a line written by the `CloudWatchFormatter`, with or without its request ID.
     *
     * @var string
     */
    private const CLOUDWATCH_LINE_PATTERN = '/^([^\t]+\t)?(DEBUG|INFO|NOTICE|WARNING|ERROR|CRITICAL|ALERT|EMERGENCY)\t.*\t\{.*\}$/';

    /**
     * The longest incomplete line kept in the buffer, matching the PHP-FPM `log_limit`.
     *
     * @var int
     */
    private const MAX_BUFFER_LENGTH = 256000;

    /**
     * The incomplete line waiting for the rest of the process output.
     *
     * @var string
     */
    private $buffer;

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
        $this->buffer = '';
        $this->logger = $logger;
    }

    /**
     * Log the incomplete line left in the buffer.
     */
    public function flush(): void
    {
        $line = $this->buffer;

        $this->buffer = '';

        $this->logLine($line);
    }

    /**
     * Log every complete line of the given process output and buffer the incomplete one.
     */
    public function log(string $output): void
    {
        $lines = explode("\n", $this->buffer.$output);

        $this->buffer = array_pop($lines);

        foreach ($lines as $line) {
            $this->logLine($line);
        }

        if (strlen($this->buffer) > self::MAX_BUFFER_LENGTH) {
            $this->flush();
        }
    }

    /**
     * Checks if the given line is a JSON object or a line written by the `CloudWatchFormatter`.
     */
    private function isFormatted(string $line): bool
    {
        return '{' === $line[0] && str_ends_with($line, '}') ? null !== json_decode($line) : 1 === preg_match(self::CLOUDWATCH_LINE_PATTERN, $line);
    }

    /**
     * Log the given line, formatting it only when it isn't formatted already.
     */
    private function logLine(string $line): void
    {
        $trimmedLine = trim($line);

        if ('' === $trimmedLine) {
            return;
        }

        if (!$this->isFormatted($trimmedLine)) {
            $this->logger->info($line);

            return;
        }

        if ($this->logger->isHandling(Logger::INFO)) {
            $this->logger->write($trimmedLine."\n");
        }
    }
}
