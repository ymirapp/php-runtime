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

namespace Placeholder\Runtime\Lambda\InvocationEvent;

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
    public static function createFromApi($handle): InvocationEventInterface
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

        $event = json_decode($body, true);

        if (!is_array($event)) {
            throw new \Exception('Unable to decode the Lambda runtime API response');
        }

        return self::createInvocationEvent($requestId, $event);
    }

    /**
     * Creates a new invocation event object based on the given event information from the Lambda runtime API.
     */
    private static function createInvocationEvent($requestId, array $event): InvocationEventInterface
    {
        $invocationEvent = null;

        if (isset($event['command'])) {
            $invocationEvent = new ConsoleCommandEvent($requestId, $event);
        } elseif (isset($event['httpMethod'])) {
            $invocationEvent = new HttpRequestEvent($requestId, $event);
        } elseif (isset($event['ping']) && true === $event['ping']) {
            $invocationEvent = new PingEvent($requestId, $event);
        } elseif (isset($event['php'])) {
            $invocationEvent = new PhpConsoleCommandEvent($requestId, $event);
        }

        if (!$invocationEvent instanceof InvocationEventInterface) {
            throw new \Exception('Unknown Lambda event type');
        }

        return $invocationEvent;
    }
}
