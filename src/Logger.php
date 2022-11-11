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

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;

/**
 * Logger that outputs logs to CloudWatch.
 */
class Logger extends MonologLogger
{
    /**
     * The logging level of the logger.
     *
     * @var int
     */
    private $level;

    /**
     * The stream to use with the logger.
     *
     * @var resource|string
     */
    private $stream;

    /**
     * Constructor.
     */
    public function __construct($level, $stream = STDERR)
    {
        $this->level = self::toMonologLevel($level);
        $this->stream = $stream;

        parent::__construct('ymir', [$this->getStreamHandler()]);
    }

    /**
     * {@inheritdoc}
     */
    public function addRecord($level, $message, array $context = []): bool
    {
        // When killing the Lambda container, it appears that PHP closes the stream with the `__destruct` method before
        // we're done sending logs. We use a try/catch block here in order to reset the stream handler so we can send
        // the final logs from process shutdown.
        try {
            return parent::addRecord($level, $message, $context);
        } catch (\LogicException $exception) {
            $this->setHandlers([$this->getStreamHandler()]);

            return parent::addRecord($level, $message, $context);
        }
    }

    /**
     * Logs an exception.
     */
    public function exception(\Throwable $exception): void
    {
        $errorMessage = $exception->getMessage();

        if ($exception instanceof \Exception) {
            $errorMessage = sprintf('Uncaught %s: %s', get_class($exception), $errorMessage);
        }

        $this->alert(sprintf(
            "Fatal error: %s in %s:%d\nStack trace:\n%s",
            $errorMessage,
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        ));
    }

    /**
     * Get the stream handler used by the logger.
     */
    private function getStreamHandler(): StreamHandler
    {
        $handler = new StreamHandler($this->stream, $this->level);

        $handler->setFormatter(new LineFormatter("%message% %context% %extra%\n", null, true, true));

        return $handler;
    }
}
