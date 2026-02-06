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

use Ymir\Runtime\Exception\RuntimeApiException;
use Ymir\Runtime\Exception\UnsupportedEventException;
use Ymir\Runtime\Logger;

/**
 * Factory that creates Lambda invocation events from the runtime API.
 */
class InvocationEventFactory
{
    /**
     * Create a new Lambda event from the Lambda next invocation API.
     *
     * This call is blocking because the Lambda runtime API is blocking.
     */
    public static function createFromApi($handle, Logger $logger): InvocationEventInterface
    {
        if (!self::isHandle($handle)) {
            throw new RuntimeApiException('The given "handle" must be a resource or a CurlHandle object');
        }

        $headers = [];
        curl_setopt($handle, CURLOPT_HEADERFUNCTION, function ($handle, $header) use (&$headers) {
            if (!preg_match('/:\s*/', $header)) {
                return strlen($header);
            }

            [$name, $value] = (array) preg_split('/:\s*/', $header, 2);

            $headers[strtolower((string) $name)] = trim((string) $value);

            return strlen($header);
        });

        $body = '';
        curl_setopt($handle, CURLOPT_WRITEFUNCTION, function ($handle, $chunk) use (&$body) {
            $body .= $chunk;

            return strlen($chunk);
        });

        curl_exec($handle);

        if (curl_error($handle)) {
            throw new RuntimeApiException('Failed to get the next Lambda invocation: '.curl_error($handle));
        } elseif ('' === $body) {
            throw new RuntimeApiException('Unable to parse the Lambda runtime API response');
        }

        $context = InvocationContext::fromHeaders($headers);
        $event = json_decode($body, true);

        if (!is_array($event)) {
            throw new RuntimeApiException('Unable to decode the Lambda runtime API response');
        }

        $logger->debug('Lambda event received:', [
            'context' => $context,
            'event' => $event,
        ]);

        return self::createFromInvocationEvent($context, $event);
    }

    /**
     * Creates a new invocation event object based on the given event information from the Lambda runtime API.
     */
    public static function createFromInvocationEvent(InvocationContext $context, array $event): InvocationEventInterface
    {
        $invocationEvent = null;

        if (isset($event['command'])) {
            $invocationEvent = new ConsoleCommandEvent($context, (string) $event['command']);
        } elseif (isset($event['httpMethod']) || isset($event['requestContext']['http']['method'])) {
            $invocationEvent = new HttpRequestEvent($context, $event);
        } elseif (isset($event['ping']) && true === $event['ping']) {
            $invocationEvent = new PingEvent($context);
        } elseif (isset($event['php'])) {
            $invocationEvent = new PhpConsoleCommandEvent($context, (string) $event['php']);
        } elseif (isset($event['Records'][0]['eventSource']) && 'aws:sqs' === $event['Records'][0]['eventSource']) {
            $invocationEvent = new SqsEvent($context, $event);
        } elseif (isset($event['warmup'])) {
            $invocationEvent = new WarmUpEvent($context, (int) $event['warmup']);
        }

        if (!$invocationEvent instanceof InvocationEventInterface) {
            throw new UnsupportedEventException('Unknown Lambda event type');
        }

        return $invocationEvent;
    }

    /**
     * Checks if we have a valid cURL handle.
     */
    private static function isHandle($handle): bool
    {
        return (\PHP_VERSION_ID < 80000 && is_resource($handle))
            || (\PHP_VERSION_ID >= 80000 && $handle instanceof \CurlHandle);
    }
}
