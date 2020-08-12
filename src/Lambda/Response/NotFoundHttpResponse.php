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
 * A Lambda response for a 404 HTTP response.
 */
class NotFoundHttpResponse extends HttpResponse
{
    /**
     * Constructor.
     */
    public function __construct(string $body = '', array $headers = [])
    {
        parent::__construct($body, $headers, 404);
    }
}
