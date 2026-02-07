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

namespace Ymir\Runtime\Exception;

/**
 * Exception thrown when an SQS record fails to be processed.
 */
class SqsRecordProcessingException extends \RuntimeException implements ExceptionInterface
{
    /**
     * Constructor.
     */
    public function __construct(string $message, string $error = '')
    {
        $fullMessage = $message;

        if (!empty($error)) {
            $fullMessage .= sprintf(': %s', $error);
        }

        parent::__construct($fullMessage);
    }
}
