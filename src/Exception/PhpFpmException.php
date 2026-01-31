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
 * Exception thrown when there is an error with the PHP-FPM process.
 */
class PhpFpmException extends \RuntimeException implements ExceptionInterface
{
}
