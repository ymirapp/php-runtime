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

        $logger->info('Lambda event received:', $event);

        return self::createFromInvocationEvent($requestId, $event);
    }

    /**
     * Creates a new invocation event object based on the given event information from the Lambda runtime API.
     */
    public static function createFromInvocationEvent(string $requestId, array $event): InvocationEventInterface
    {
        $invocationEvent = null;

        if (isset($event['command'])) {
            $invocationEvent = new ConsoleCommandEvent($requestId, (string) $event['command']);
        } elseif (isset($event['httpMethod'])) {
            $invocationEvent = new HttpRequestEvent($requestId, $event);
        } elseif (isset($event['ping']) && true === $event['ping']) {
            $invocationEvent = new PingEvent($requestId);
        } elseif (isset($event['php'])) {
            $invocationEvent = new PhpConsoleCommandEvent($requestId, (string) $event['php']);
        }

        if (!$invocationEvent instanceof InvocationEventInterface) {
            throw new \InvalidArgumentException('Unknown Lambda event type');
        }

        return $invocationEvent;
    }
}
