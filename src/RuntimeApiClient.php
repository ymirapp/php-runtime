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

namespace Ymir\Runtime;

use Ymir\Runtime\Exception\RuntimeApiException;
use Ymir\Runtime\Lambda\InvocationEvent\Context;
use Ymir\Runtime\Lambda\InvocationEvent\InvocationEventFactory;
use Ymir\Runtime\Lambda\InvocationEvent\InvocationEventInterface;
use Ymir\Runtime\Lambda\Response\Http\ForbiddenHttpResponse;
use Ymir\Runtime\Lambda\Response\ResponseInterface;

/**
 * Client for interacting with the AWS Lambda runtime API.
 */
class RuntimeApiClient
{
    /**
     * The URL of the Lambda runtime API.
     *
     * @var string
     */
    private $apiUrl;

    /**
     * The logger that sends logs to CloudWatch.
     *
     * @var Logger
     */
    private $logger;

    /**
     * The cURL handle for the Lambda next invocation API.
     *
     * @var \CurlHandle|resource
     */
    private $nextInvocationHandle;

    /**
     * Constructor.
     */
    public function __construct(string $apiUrl, Logger $logger)
    {
        $handle = curl_init("http://$apiUrl/2018-06-01/runtime/invocation/next");

        if (false === $handle) {
            throw new RuntimeApiException('Failed to connect to the AWS Lambda next invocation API');
        }

        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($handle, CURLOPT_FAILONERROR, true);

        $this->apiUrl = $apiUrl;
        $this->logger = $logger;
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
    public function getNextEvent(): InvocationEventInterface
    {
        return InvocationEventFactory::createFromApi($this->nextInvocationHandle, $this->logger);
    }

    /**
     * Send an error back to the Lambda runtime API for the given event.
     */
    public function sendError(Context $context, \Throwable $error): void
    {
        $this->sendData($this->getErrorData($error), sprintf('invocation/%s/error', $context->getRequestId()));
    }

    /**
     * Send an initialization error to the Lambda runtime API.
     */
    public function sendInitializationError(\Throwable $error): void
    {
        $this->sendData($this->getErrorData($error), 'init/error');
    }

    /**
     * Send a response to the Lambda runtime API for the given event.
     */
    public function sendResponse(InvocationEventInterface $event, ResponseInterface $response): void
    {
        $data = $response->getResponseData();

        // Lambda has a 6MB response payload limit. Send an error if we hit this limit instead of getting an
        // error from the API gateway.
        if (!empty($data['body']) && mb_strlen((string) $data['body']) >= 6000000) {
            $data = (new ForbiddenHttpResponse('Response Too Large'))->getResponseData();
        }

        $this->sendData($data, sprintf('invocation/%s/response', $event->getContext()->getRequestId()));
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
    private function sendData($data, string $uri): void
    {
        $json = json_encode($data);

        if (false === $json) {
            throw new RuntimeApiException('Error encoding JSON data: '.json_last_error_msg());
        }

        $url = "http://{$this->apiUrl}/2018-06-01/runtime/".ltrim($uri, '/');
        $handle = curl_init($url);

        if (false === $handle) {
            throw new RuntimeApiException('Unable to initialize curl session for: '.$url);
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

            throw new RuntimeApiException('Error sending data to the Lambda runtime API: '.$errorMessage);
        }

        curl_setopt($handle, CURLOPT_HEADERFUNCTION, null);
        curl_setopt($handle, CURLOPT_READFUNCTION, null);
        curl_setopt($handle, CURLOPT_WRITEFUNCTION, null);
        curl_setopt($handle, CURLOPT_PROGRESSFUNCTION, null);

        curl_reset($handle);

        curl_close($handle);
    }
}
