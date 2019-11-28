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

use hollodotme\FastCGI\Interfaces\ProvidesRequestData;
use Placeholder\Runtime\Lambda\LambdaInvocationEvent;

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
    public function __construct(string $content, array $parameters)
    {
        $this->content = $content;
        $this->parameters = $parameters;
    }

    /**
     * Create new FastCGI request from a Lambda invocation event.
     */
    public static function createFromInvocationEvent(LambdaInvocationEvent $event, string $scriptFilename): self
    {
        $content = $event->getRequestBody();
        $headers = $event->getRequestHeaders();
        $host = $headers['host'][0] ?? 'localhost';
        $method = strtoupper($event->getRequestMethod());
        $path = $uri = $event->getRequestPath();
        $port = $headers['x-forwarded-port'][0] ?? 80;
        $queryString = $event->getRequestQueryString();

        if (!empty($queryString)) {
            $uri = $uri.'?'.$queryString;
        }

        $parameters = [
            'GATEWAY_INTERFACE' => 'FastCGI/1.0',
            'PATH_INFO' => $path,
            'QUERY_STRING' => $queryString,
            'REMOTE_ADDR' => '127.0.0.1',
            'REMOTE_PORT' => $port,
            'REQUEST_METHOD' => $method,
            'REQUEST_URI' => $uri,
            'SCRIPT_FILENAME' => $scriptFilename,
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_NAME' => $host,
            'SERVER_PORT' => $port,
            'SERVER_PROTOCOL' => $event->getRequestProtocol(),
            'SERVER_SOFTWARE' => 'placeholder',
        ];

        if (isset($headers['x-forwarded-proto'][0]) && 'https' == strtolower($headers['x-forwarded-proto'][0])) {
            $parameters['HTTPS'] = 'on';
        }

        if (isset($headers['content-length'][0])) {
            $parameters['CONTENT_LENGTH'] = $headers['content-length'][0];
        } elseif ('TRACE' === $method) {
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
        return (int) $this->parameters['CONTENT_LENGTH'] ?? 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getContentType(): string
    {
        return $this->parameters['CONTENT_TYPE'];
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
        return $this->parameters['GATEWAY_INTERFACE'] ?? 'FastCGI/1.0';
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
        return $this->parameters['REMOTE_ADDR'];
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestMethod(): string
    {
        return $this->parameters['REQUEST_METHOD'];
    }

    /**
     * {@inheritdoc}
     */
    public function getRemotePort(): int
    {
        return (int) $this->parameters['SERVER_PORT'];
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestUri(): string
    {
        return $this->parameters['REQUEST_URI'];
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
        return $this->parameters['SCRIPT_FILENAME'];
    }

    /**
     * {@inheritdoc}
     */
    public function getServerAddress(): string
    {
        return $this->parameters['SERVER_ADDR'];
    }

    /**
     * {@inheritdoc}
     */
    public function getServerName(): string
    {
        return $this->parameters['SERVER_NAME'];
    }

    /**
     * {@inheritdoc}
     */
    public function getServerPort(): int
    {
        return (int) $this->parameters['SERVER_PORT'];
    }

    /**
     * {@inheritdoc}
     */
    public function getServerProtocol(): string
    {
        return $this->parameters['SERVER_PROTOCOL'];
    }

    /**
     * {@inheritdoc}
     */
    public function getServerSoftware(): string
    {
        return $this->parameters['SERVER_SOFTWARE'];
    }
}
