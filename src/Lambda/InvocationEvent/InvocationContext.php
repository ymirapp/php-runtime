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

/**
 * The Lambda invocation context.
 */
class InvocationContext implements \JsonSerializable
{
    /**
     * The deadline for the Lambda invocation in milliseconds.
     *
     * @var int
     */
    private $deadlineMs;

    /**
     * The ARN of the invoked function.
     *
     * @var string
     */
    private $invokedFunctionArn;

    /**
     * The ID of the Lambda invocation request.
     *
     * @var string
     */
    private $requestId;

    /**
     * The trace ID of the Lambda invocation.
     *
     * @var string
     */
    private $traceId;

    /**
     * Constructor.
     */
    public function __construct(string $requestId, int $deadlineMs = 0, string $invokedFunctionArn = '', string $traceId = '')
    {
        $this->deadlineMs = $deadlineMs;
        $this->invokedFunctionArn = $invokedFunctionArn;
        $this->requestId = $requestId;
        $this->traceId = $traceId;
    }

    /**
     * Create a new Lambda context from the runtime API headers.
     */
    public static function fromHeaders(array $headers): self
    {
        if (empty($headers['lambda-runtime-aws-request-id'])) {
            throw new RuntimeApiException('Unable to determine the Lambda invocation ID');
        }

        return new self(
            $headers['lambda-runtime-aws-request-id'],
            (int) ($headers['lambda-runtime-deadline-ms'] ?? 0),
            $headers['lambda-runtime-invoked-function-arn'] ?? '',
            $headers['lambda-runtime-trace-id'] ?? ''
        );
    }

    /**
     * Get the deadline for the Lambda invocation in milliseconds.
     */
    public function getDeadlineMs(): int
    {
        return $this->deadlineMs;
    }

    /**
     * Get the ARN of the invoked function.
     */
    public function getInvokedFunctionArn(): string
    {
        return $this->invokedFunctionArn;
    }

    /**
     * Get the remaining time in milliseconds before the execution times out.
     */
    public function getRemainingTimeInMs(): int
    {
        return max(0, $this->deadlineMs - (int) (microtime(true) * 1000));
    }

    /**
     * Get the ID of the Lambda invocation request.
     */
    public function getRequestId(): string
    {
        return $this->requestId;
    }

    /**
     * Get the trace ID of the Lambda invocation.
     */
    public function getTraceId(): string
    {
        return $this->traceId;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return [
            'awsRequestId' => $this->requestId,
            'deadlineMs' => $this->deadlineMs,
            'invokedFunctionArn' => $this->invokedFunctionArn,
            'traceId' => $this->traceId,
        ];
    }
}
