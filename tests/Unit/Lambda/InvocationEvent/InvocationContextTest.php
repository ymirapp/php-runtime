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

namespace Ymir\Runtime\Tests\Unit\Lambda\InvocationEvent;

use PHPUnit\Framework\TestCase;
use Ymir\Runtime\Exception\RuntimeApiException;
use Ymir\Runtime\Lambda\InvocationEvent\InvocationContext;

class InvocationContextTest extends TestCase
{
    public function testFromHeadersFailsIfRequestIdIsMissing(): void
    {
        $this->expectException(RuntimeApiException::class);
        $this->expectExceptionMessage('Unable to determine the Lambda invocation ID');

        InvocationContext::fromHeaders([]);
    }

    public function testFromHeadersWithAllHeaders(): void
    {
        $context = InvocationContext::fromHeaders([
            'lambda-runtime-aws-request-id' => 'request-id',
            'lambda-runtime-deadline-ms' => '123456789',
            'lambda-runtime-invoked-function-arn' => 'arn',
            'lambda-runtime-trace-id' => 'trace-id',
        ]);

        $this->assertSame('request-id', $context->getRequestId());
        $this->assertSame(123456789, $context->getDeadlineMs());
        $this->assertSame('arn', $context->getInvokedFunctionArn());
        $this->assertSame('trace-id', $context->getTraceId());
    }

    public function testGetDeadlineMsDefaultValue(): void
    {
        $this->assertSame(0, (new InvocationContext('request-id'))->getDeadlineMs());
    }

    public function testGetInvokedFunctionArnDefaultValue(): void
    {
        $this->assertSame('', (new InvocationContext('request-id'))->getInvokedFunctionArn());
    }

    public function testGetRemainingTimeInMs(): void
    {
        $context = new InvocationContext('request-id', (int) (microtime(true) * 1000) + 1000);

        $this->assertGreaterThan(0, $context->getRemainingTimeInMs());
        $this->assertLessThanOrEqual(1000, $context->getRemainingTimeInMs());
    }

    public function testGetRemainingTimeInMsWithNoDeadline(): void
    {
        $this->assertSame(0, (new InvocationContext('request-id'))->getRemainingTimeInMs());
    }

    public function testGetRequestId(): void
    {
        $this->assertSame('request-id', (new InvocationContext('request-id'))->getRequestId());
    }

    public function testGetTraceIdDefaultValue(): void
    {
        $this->assertSame('', (new InvocationContext('request-id'))->getTraceId());
    }

    public function testJsonSerialize(): void
    {
        $context = new InvocationContext('request-id', 123456789, 'arn', 'trace-id');

        $this->assertSame([
            'awsRequestId' => 'request-id',
            'deadlineMs' => 123456789,
            'invokedFunctionArn' => 'arn',
            'traceId' => 'trace-id',
        ], $context->jsonSerialize());
    }
}
