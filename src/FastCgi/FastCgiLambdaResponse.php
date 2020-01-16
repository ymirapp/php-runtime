<?php

declare(strict_types=1);

/*
 * This file is part of Placeholder PHP Runtime.
 *
 * (c) Carl Alexander <contact@carlalexander.ca>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Placeholder\Runtime\FastCgi;

use hollodotme\FastCGI\Interfaces\ProvidesResponseData;
use Placeholder\Runtime\Lambda\LambdaResponse;

/**
 * A Lambda response from a FastCGI server.
 */
class FastCgiLambdaResponse extends LambdaResponse
{
    /**
     * {@inheritdoc}
     */
    public function __construct(ProvidesResponseData $response)
    {
        $headers = array_change_key_case($response->getHeaders(), CASE_LOWER);
        $statusCode = 200;

        if (isset($headers['status'][0])) {
            $statusCode = (int) explode(' ', $headers['status'][0])[0];
        }

        unset($headers['status']);

        parent::__construct($response->getBody(), $headers, $statusCode);
    }
}
