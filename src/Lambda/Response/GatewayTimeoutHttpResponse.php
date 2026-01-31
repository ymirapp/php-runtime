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

namespace Ymir\Runtime\Lambda\Response;

/**
 * A Lambda response for a 504 HTTP response.
 */
class GatewayTimeoutHttpResponse extends AbstractErrorHttpResponse
{
    /**
     * Constructor.
     */
    public function __construct(string $message = 'Gateway Timeout', string $templatesDirectory = '')
    {
        parent::__construct($message, 504, $templatesDirectory);
    }
}
