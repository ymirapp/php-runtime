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

use hollodotme\FastCGI\Interfaces\ProvidesRequestData;
use Ymir\Runtime\Lambda\InvocationEvent\HttpRequestEvent;

/**
 * A request sent to a FastCGI server.
 */
class FastCgiRequest implements ProvidesRequestData
{
    /**
     * The content of the request.
     *
     * @var string
     */
    private $content;

    /**
     * Parameters to send to the FastCGI server.
     *
     * @var array
     */
    private $parameters;

    /**
     * Constructor.
     */
    public function __construct(string $content = '', array $parameters = [])
    {
        $this->content = $content;
        $this->parameters = array_change_key_case($parameters, CASE_UPPER);
    }

    /**
     * Create new FastCGI request from a Lambda invocation event.
     */
    public static function createFromInvocationEvent(HttpRequestEvent $event, string $scriptFilename): self
    {
        $content = $event->getBody();
        $documentRoot = (string) getcwd();
        $headers = $event->getHeaders();
        $host = $headers['x-forwarded-host'][0] ?? $headers['host'][0] ?? 'localhost';
        $method = strtoupper($event->getMethod());
        $path = $uri = $event->getPath();
        $port = $headers['x-forwarded-port'][0] ?? 80;
        $queryString = $event->getQueryString();
        $scriptName = str_replace($documentRoot, '', $scriptFilename);

        $parameters = [
            'DOCUMENT_ROOT' => $documentRoot,
            'GATEWAY_INTERFACE' => 'FastCGI/1.0',
            'PATH_INFO' => $path,
            'PHP_SELF' => '/'.trim($scriptName.$uri, '/'),
            'QUERY_STRING' => $queryString,
            'REMOTE_ADDR' => $headers['x-forwarded-for'][0] ?? $event->getSourceIp(),
            'REMOTE_PORT' => $port,
            'REQUEST_METHOD' => $method,
            'REQUEST_TIME' => time(),
            'REQUEST_TIME_FLOAT' => microtime(true),
            'SCRIPT_FILENAME' => $scriptFilename,
            'SCRIPT_NAME' => $scriptName,
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_NAME' => $host,
            'SERVER_PORT' => $port,
            'SERVER_PROTOCOL' => $event->getProtocol(),
            'SERVER_SOFTWARE' => 'ymir',
        ];

        $parameters['REQUEST_URI'] = empty($queryString) ? $uri : $uri.'?'.$queryString;

        if (isset($headers['x-forwarded-proto'][0]) && 'https' == strtolower($headers['x-forwarded-proto'][0])) {
            $parameters['HTTPS'] = 'on';
        }

        if (isset($headers['content-length'][0])) {
            $parameters['CONTENT_LENGTH'] = $headers['content-length'][0];
        } elseif ('TRACE' !== $method) {
            $parameters['CONTENT_LENGTH'] = strlen($content);
        }

        if (isset($headers['content-type'][0])) {
            $parameters['CONTENT_TYPE'] = $headers['content-type'][0];
        } elseif ('POST' === $method) {
            $parameters['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
        }

        foreach ($headers as $header => $value) {
            $parameters['HTTP_'.strtoupper(str_replace('-', '_', $header))] = $value[0];
        }

        // Force "HTTP_HOST" and "SERVER_NAME" to match because of the "X_FORWARDED_HOST" header.
        $parameters['HTTP_HOST'] = $parameters['SERVER_NAME'];

        ksort($parameters);

        return new self($content, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * {@inheritdoc}
     */
    public function getContentLength(): int
    {
        return (int) ($this->parameters['CONTENT_LENGTH'] ?? 0);
    }

    /**
     * {@inheritdoc}
     */
    public function getContentType(): string
    {
        return (string) ($this->parameters['CONTENT_TYPE'] ?? 'application/x-www-form-urlencoded');
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomVars(): array
    {
        return $this->parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function getFailureCallbacks(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getGatewayInterface(): string
    {
        return (string) ($this->parameters['GATEWAY_INTERFACE'] ?? 'FastCGI/1.0');
    }

    /**
     * {@inheritdoc}
     */
    public function getParams(): array
    {
        return $this->parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function getPassThroughCallbacks(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getRemoteAddress(): string
    {
        return (string) ($this->parameters['REMOTE_ADDR'] ?? '192.168.0.1');
    }

    /**
     * {@inheritdoc}
     */
    public function getRemotePort(): int
    {
        return (int) ($this->parameters['REMOTE_PORT'] ?? 9985);
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestMethod(): string
    {
        return strtoupper((string) ($this->parameters['REQUEST_METHOD'] ?? 'GET'));
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestUri(): string
    {
        return (string) ($this->parameters['REQUEST_URI'] ?? '');
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseCallbacks(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getScriptFilename(): string
    {
        return (string) ($this->parameters['SCRIPT_FILENAME'] ?? '');
    }

    /**
     * {@inheritdoc}
     */
    public function getServerAddress(): string
    {
        return (string) ($this->parameters['SERVER_ADDR'] ?? '127.0.0.1');
    }

    /**
     * {@inheritdoc}
     */
    public function getServerName(): string
    {
        return (string) ($this->parameters['SERVER_NAME'] ?? 'localhost');
    }

    /**
     * {@inheritdoc}
     */
    public function getServerPort(): int
    {
        return (int) ($this->parameters['SERVER_PORT'] ?? 80);
    }

    /**
     * {@inheritdoc}
     */
    public function getServerProtocol(): string
    {
        return (string) ($this->parameters['SERVER_PROTOCOL'] ?? 'HTTP/1.1');
    }

    /**
     * {@inheritdoc}
     */
    public function getServerSoftware(): string
    {
        return (string) ($this->parameters['SERVER_SOFTWARE'] ?? 'ymir');
    }
}
