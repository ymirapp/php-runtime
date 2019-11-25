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
        $headers = $this->parseHeaders($response);
        $statusCode = 200;

        if (isset($headers['status'][0])) {
            $statusCode = (int) explode(' ', $headers['status'][0])[0];
        }

        unset($headers['status']);

        parent::__construct($response->getBody(), $headers, $statusCode);
    }

    /**
     * Parse the HTTP headers from the FastCGI server response.
     */
    private function parseHeaders(ProvidesResponseData $response): array
    {
        $headers = [];

        foreach (explode(PHP_EOL, $response->getOutput()) as $line) {
            if (preg_match('#^([^\:]+):(.*)$#', $line, $matches)) {
                $headers[trim($matches[1])][] = trim($matches[2]);

                continue;
            }

            break;
        }

        return array_change_key_case($headers, CASE_LOWER);
    }
}
