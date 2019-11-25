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
 * Client for interacting with the AWS Lambda runtime API.
 */
class LambdaRuntimeApiClient
{
    /**
     * The URL of the Lambda runtime API.
     *
     * @var string
     */
    private $apiUrl;

    /**
     * The cURL handle for the Lambda next invocation API.
     *
     * @var resource
     */
    private $nextInvocationHandle;

    /**
     * Constructor.
     */
    public function __construct(string $apiUrl)
    {
        $handle = curl_init("http://$apiUrl/2018-06-01/runtime/invocation/next");

        if (!is_resource($handle)) {
            throw new \RuntimeException('Failed to connect to the AWS Lambda next invocation API');
        }

        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($handle, CURLOPT_FAILONERROR, true);

        $this->apiUrl = $apiUrl;
        $this->nextInvocationHandle = $handle;
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        curl_close($this->nextInvocationHandle);
    }

    /**
     * Get the next Lambda invocation event.
     *
     * This call is blocking because the Lambda runtime API is blocking.
     */
    public function getNextEvent(): LambdaInvocationEvent
    {
        return LambdaInvocationEvent::createFromApi($this->nextInvocationHandle);
    }

    /**
     * Send an error back to the Lambda runtime API for the given event.
     */
    public function sendEventError(LambdaInvocationEvent $event, \Throwable $error)
    {
        $this->sendData($this->getErrorData($error), "invocation/{$event->getId()}/error");
    }

    /**
     * Send an initialization error to the Lambda runtime API.
     */
    public function sendInitializationError(\Throwable $error)
    {
        $this->sendData($this->getErrorData($error), 'init/error');
    }

    /**
     * Send a response to the Lambda runtime API for the given event.
     */
    public function sendResponse(LambdaInvocationEvent $event, LambdaResponse $response)
    {
        $this->sendData($response->getResponseData(), "invocation/{$event->getId()}/response");
    }

    /**
     * Get the error data to send to the Lambda runtime API for the given exception.
     */
    private function getErrorData(\Throwable $error): array
    {
        return [
            'errorMessage' => $error->getMessage(),
            'errorType' => get_class($error),
            'stackTrace' => explode(PHP_EOL, $error->getTraceAsString()),
        ];
    }

    /**
     * Send data back to the Lambda runtime API.
     */
    private function sendData($data, string $uri)
    {
        $json = json_encode($data);

        if (false === $json) {
            throw new \Exception('Error encoding JSON data: '.json_last_error_msg());
        }

        $url = "http://{$this->apiUrl}/2018-06-01/runtime/".ltrim($uri, '/');
        $handle = curl_init($url);

        if (!is_resource($handle)) {
            throw new \Exception('Unable to initialize curl session for: '.$url);
        }

        curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $json);
        curl_setopt($handle, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: '.strlen($json),
        ]);

        curl_exec($handle);

        if (curl_error($handle)) {
            $errorMessage = curl_error($handle);

            throw new \Exception('Error sending data to the Lambda runtime API: '.$errorMessage);
        }

        curl_setopt($handle, CURLOPT_HEADERFUNCTION, null);
        curl_setopt($handle, CURLOPT_READFUNCTION, null);
        curl_setopt($handle, CURLOPT_WRITEFUNCTION, null);
        curl_setopt($handle, CURLOPT_PROGRESSFUNCTION, null);

        curl_reset($handle);

        curl_close($handle);
    }
}
