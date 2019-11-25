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

namespace Placeholder\Runtime\Lambda;

/**
 * A Lambda invocation event from the runtime API.
 */
class LambdaInvocationEvent
{
    /**
     * The Lambda event details.
     *
     * @var array
     */
    private $event;

    /**
     * The ID of the Lambda invocation.
     *
     * @var string
     */
    private $id;

    /**
     * Constructor.
     */
    public function __construct(string $id, array $event = [])
    {
        $this->event = $event;
        $this->id = $id;
    }

    /**
     * Create a new Lambda event from the Lambda next invocation API.
     *
     * This call is blocking because the Lambda runtime API is blocking.
     */
    public static function createFromApi($handle): self
    {
        if (!is_resource($handle)) {
            throw new \RuntimeException('The given "handle" must be a resource');
        }

        $requestId = '';
        curl_setopt($handle, CURLOPT_HEADERFUNCTION, function ($handle, $header) use (&$requestId) {
            if (!preg_match('/:\s*/', $header)) {
                return strlen($header);
            }

            list($name, $value) = preg_split('/:\s*/', $header, 2);

            if ('lambda-runtime-aws-request-id' == strtolower((string) $name)) {
                $requestId = trim((string) $value);
            }

            return strlen($header);
        });

        $body = '';
        curl_setopt($handle, CURLOPT_WRITEFUNCTION, function ($handle, $chunk) use (&$body) {
            $body .= $chunk;

            return strlen($chunk);
        });

        curl_exec($handle);

        if (curl_error($handle)) {
            throw new \Exception('Failed to get the next Lambda invocation: '.curl_error($handle));
        } elseif ('' === $requestId) {
            throw new \Exception('Unable to parse the Lambda invocation ID');
        } elseif ('' === $body) {
            throw new \Exception('Unable to parse the Lambda runtime API response');
        }

        return new self($requestId, json_decode($body, true));
    }

    /**
     * Get the ID of the Lambda invocation.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the body of the invocation request.
     */
    public function getRequestBody(): string
    {
        $body = $this->event['body'] ?? '';

        if (!empty($this->event['isBase64Encoded'])) {
            $body = base64_decode($body);
        }

        return $body;
    }

    /**
     * Get the headers of the invocation request.
     */
    public function getRequestHeaders(): array
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
    public function getRequestMethod(): string
    {
        return $this->event['httpMethod'] ?? 'GET';
    }

    /**
     * Get the path of the invocation request.
     */
    public function getRequestPath(): string
    {
        return $this->event['path'] ?? '/';
    }

    /**
     * Get the protocol used by the invocation request.
     */
    public function getRequestProtocol(): string
    {
        return $this->event['requestContext']['protocol'] ?? 'HTTP/1.1';
    }

    /**
     * Get the query string of the invocation request.
     */
    public function getRequestQueryString(): string
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
