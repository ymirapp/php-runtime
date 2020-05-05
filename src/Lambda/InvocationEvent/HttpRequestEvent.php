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

namespace Ymir\Runtime\Lambda\InvocationEvent;

/**
 * Lambda invocation event for an HTTP request.
 */
class HttpRequestEvent extends AbstractEvent
{
    /**
     * Get the body of the invocation request.
     */
    public function getBody(): string
    {
        $body = (string) ($this->event['body'] ?? '');

        if (!empty($this->event['isBase64Encoded'])) {
            $body = base64_decode($body);
        }

        return $body;
    }

    /**
     * Get the headers of the invocation request.
     */
    public function getHeaders(): array
    {
        $headers = [];

        if (isset($this->event['multiValueHeaders'])) {
            $headers = $this->event['multiValueHeaders'];
        } elseif (isset($this->event['headers'])) {
            $headers = array_map(function ($value) {
                return [$value];
            }, $this->event['headers']);
        }

        return array_change_key_case($headers, CASE_LOWER);
    }

    /**
     * Get the HTTP method of the invocation request.
     */
    public function getMethod(): string
    {
        return strtoupper((string) ($this->event['httpMethod'] ?? 'GET'));
    }

    /**
     * Get the path of the invocation request.
     */
    public function getPath(): string
    {
        return (string) ($this->event['path'] ?? '/');
    }

    /**
     * Get the protocol used by the invocation request.
     */
    public function getProtocol(): string
    {
        return (string) ($this->event['requestContext']['protocol'] ?? 'HTTP/1.1');
    }

    /**
     * Get the query string of the invocation request.
     */
    public function getQueryString(): string
    {
        if (empty($this->event['queryStringParameters']) && empty($this->event['multiValueQueryStringParameters'])) {
            return '';
        } elseif (!empty($this->event['queryStringParameters'])) {
            return http_build_query($this->event['queryStringParameters']);
        }

        $queryParameters = [];

        // TODO: Test this
        foreach ($this->event['multiValueQueryStringParameters'] as $parameter => $value) {
            $matches = [];
            preg_match('/([^[]*)(\[([^]]*)\])?/', $parameter, $matches);

            $value = 1 === count($value) ? $value[0] : $value;
            $value = !empty($matches[3]) ? [$matches[3] => $value] : $value;

            $queryParameters[$matches[1]] = $value;
        }

        return http_build_query($queryParameters);
    }
}
