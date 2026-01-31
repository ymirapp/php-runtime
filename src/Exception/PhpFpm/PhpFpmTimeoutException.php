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

namespace Ymir\Runtime\Exception\PhpFpm;

/**
 * Exception thrown when a request to PHP-FPM times out.
 */
class PhpFpmTimeoutException extends PhpFpmException
{
}
