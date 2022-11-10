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

use Tightenco\Collect\Support\Arr;
use Tightenco\Collect\Support\Collection;

/**
 * An HTTP lambda response.
 */
class HttpResponse implements ResponseInterface
{
    /**
     * The body of the Lambda response.
     *
     * @var string
     */
    private $body;

    /**
     * The response format version.
     *
     * @see https://docs.aws.amazon.com/apigateway/latest/developerguide/http-api-develop-integrations-lambda.html#http-api-develop-integrations-lambda.response
     *
     * @var string
     */
    private $formatVersion;

    /**
     * The headers to send with the Lambda response.
     *
     * @var array
     */
    private $headers;

    /**
     * The HTTP status code of the response.
     *
     * @var int
     */
    private $statusCode;

    /**
     * Constructor.
     */
    public function __construct(string $body, array $headers = [], int $statusCode = 200, string $formatVersion = '1.0')
    {
        if (!in_array($formatVersion, ['1.0', '2.0'])) {
            throw new \InvalidArgumentException('"formatVersion" must be either "1.0" or "2.0"');
        }

        $this->body = $body;
        $this->formatVersion = $formatVersion;
        $this->headers = $headers;
        $this->statusCode = $statusCode;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseData(): array
    {
        $data = [
            'isBase64Encoded' => true,
            'statusCode' => $this->statusCode,
        ];

        // API Gateway generates an error when sending 304 responses with a body and headers.
        if (304 === $this->statusCode) {
            return $data;
        }

        $body = $this->body;
        $headers = $this->getFormattedHeaders();
        $headersKey = '1.0' === $this->formatVersion ? 'multiValueHeaders' : 'headers';

        // Compress HTML responses if they haven't already. This reduces the chance of hitting the 6MB Lambda payload
        // limit since compression happens after the response gets sent back.
        if ($this->shouldCompressResponse($headers)) {
            $body = (string) gzencode($body, 9);
            $headers['Content-Encoding'] = ['gzip'];
            $headers['Content-Length'] = [strlen($body)];
        }

        if ('2.0' === $this->formatVersion && isset($headers['Set-Cookie'])) {
            $data['cookies'] = $headers['Set-Cookie'];
            unset($headers['Set-Cookie']);
        }

        if ('headers' === $headersKey) {
            $headers = $headers->map(function (array $values) {
                return end($values);
            });
        }

        $data['body'] = base64_encode($body);

        // PHP will serialize an empty array to `[]`. However, we need it to be an empty JSON object
        // which is `{}` so we convert an empty array to an empty object.
        $data[$headersKey] = $headers->isEmpty() ? new \stdClass() : $headers->all();

        return $data;
    }

    /**
     * Get the HTTP response headers properly formatted for a Lambda response.
     */
    private function getFormattedHeaders(): Collection
    {
        $headers = collect($this->headers)->mapWithKeys(function ($values, $name) {
            $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
            $values = array_values((array) $values);

            return [$name => $values];
        });

        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = ['text/html'];
        }

        return $headers;
    }

    /**
     * Determine if we should compress the HTTP response or not.
     */
    private function shouldCompressResponse(Collection $headers): bool
    {
        if (isset($headers['Content-Encoding']) || !isset($headers['Content-Type']) || !is_array($headers['Content-Type'])) {
            return false;
        }

        $contentType = Arr::last($headers['Content-Type']);

        if (!is_string($contentType)) {
            return false;
        }

        return 0 === stripos($contentType, 'text/html') || 0 === stripos($contentType, 'application/json');
    }
}
