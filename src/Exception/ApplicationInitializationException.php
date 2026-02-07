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
 * Exception thrown when the application fails to initialize.
 */
class ApplicationInitializationException extends \RuntimeException implements ExceptionInterface
{
}
