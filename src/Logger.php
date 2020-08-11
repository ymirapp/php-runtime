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

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;

/**
 * Logger that outputs logs to CloudWatch.
 */
class Logger extends MonologLogger
{
    /**
     * Constructor.
     */
    public function __construct($level, $stream = STDERR)
    {
        parent::__construct('ymir', [
            (new StreamHandler($stream, $level))->setFormatter(new JsonFormatter()),
        ]);
    }

    /**
     * Logs an exception.
     */
    public function exception(\Throwable $exception)
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
}
