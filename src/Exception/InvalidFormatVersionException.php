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
 * Exception thrown when an invalid format version is used.
 */
class InvalidFormatVersionException extends \InvalidArgumentException implements ExceptionInterface
{
    /**
     * Constructor.
     */
    public function __construct(string $formatVersion)
    {
        parent::__construct(sprintf('Format must be either "1.0" or "2.0", "%s" given', $formatVersion));
    }
}
