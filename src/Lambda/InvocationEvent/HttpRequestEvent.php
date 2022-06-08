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
     * The Lambda event details.
     *
     * @var array
     */
    private $event;

    /**
     * Constructor.
     */
    public function __construct(string $id, array $event = [])
    {
        parent::__construct($id);

        $this->event = $event;
    }

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

        if ('2.0' === $this->getPayloadVersion() && !empty($this->event['cookies'])) {
            $headers['cookie'] = [implode('; ', $this->event['cookies'])];
        }

        return array_change_key_case($headers, CASE_LOWER);
    }

    /**
     * Get the HTTP method of the invocation request.
     */
    public function getMethod(): string
    {
        return strtoupper((string) ($this->event['httpMethod'] ?? $this->event['requestContext']['http']['method'] ?? 'GET'));
    }

    /**
     * Get the path of the invocation request.
     */
    public function getPath(): string
    {
        return (string) ($this->event['path'] ?? $this->event['rawPath'] ?? '/');
    }

    /**
     * Get the payload version of the event.
     *
     * @see https://docs.aws.amazon.com/apigateway/latest/developerguide/http-api-develop-integrations-lambda.html#http-api-develop-integrations-lambda.proxy-format
     */
    public function getPayloadVersion(): string
    {
        return (string) ($this->event['version'] ?? '1.0');
    }

    /**
     * Get the protocol used by the invocation request.
     */
    public function getProtocol(): string
    {
        return (string) ($this->event['requestContext']['protocol'] ?? $this->event['requestContext']['http']['protocol'] ?? 'HTTP/1.1');
    }

    /**
     * Get the query string of the invocation request.
     */
    public function getQueryString(): string
    {
        $payloadVersion = $this->getPayloadVersion();
        $queryString = '';

        if ('1.0' === $payloadVersion) {
            collect($this->event['multiValueQueryStringParameters'] ?? $this->event['queryStringParameters'] ?? [])->each(function ($values, $key) use (&$queryString) {
                $queryString .= array_reduce((array) $values, function ($carry, $value) use ($key) {
                    return $carry.$key.'='.$value.'&';
                });
            });
        } elseif ('2.0' === $payloadVersion) {
            $queryString = $this->event['rawQueryString'] ?? '';
        }

        parse_str($queryString, $decodedQueryParameters);

        return http_build_query($decodedQueryParameters);
    }
}
