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

namespace Ymir\Runtime\FastCgi;

use hollodotme\FastCGI\Interfaces\ProvidesResponseData;
use Ymir\Runtime\Lambda\Response\HttpResponse;

/**
 * A Lambda response from a FastCGI server.
 */
class FastCgiHttpResponse extends HttpResponse
{
    /**
     * {@inheritdoc}
     */
    public function __construct(ProvidesResponseData $response, string $formatVersion = '1.0', bool $compressible = true)
    {
        $headers = array_change_key_case($response->getHeaders(), CASE_LOWER);
        $statusCode = 200;

        if (isset($headers['status'][0])) {
            $statusCode = (int) explode(' ', $headers['status'][0])[0];
        }

        unset($headers['status']);

        parent::__construct($response->getBody(), $headers, $statusCode, $formatVersion, $compressible);
    }
}
