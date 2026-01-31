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
use Ymir\Runtime\Lambda\InvocationEvent\Context;

/**
 * @covers \Ymir\Runtime\Lambda\InvocationEvent\Context
 */
class ContextTest extends TestCase
{
    public function testFromHeadersFailsIfRequestIdIsMissing(): void
    {
        $this->expectException(RuntimeApiException::class);
        $this->expectExceptionMessage('Unable to determine the Lambda invocation ID');

        Context::fromHeaders([]);
    }

    public function testFromHeadersWithAllHeaders(): void
    {
        $context = Context::fromHeaders([
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
        $this->assertSame(0, (new Context('request-id'))->getDeadlineMs());
    }

    public function testGetInvokedFunctionArnDefaultValue(): void
    {
        $this->assertSame('', (new Context('request-id'))->getInvokedFunctionArn());
    }

    public function testGetRequestId(): void
    {
        $this->assertSame('request-id', (new Context('request-id'))->getRequestId());
    }

    public function testGetTraceIdDefaultValue(): void
    {
        $this->assertSame('', (new Context('request-id'))->getTraceId());
    }

    public function testJsonSerialize(): void
    {
        $context = new Context('request-id', 123456789, 'arn', 'trace-id');

        $this->assertSame([
            'awsRequestId' => 'request-id',
            'deadlineMs' => 123456789,
            'invokedFunctionArn' => 'arn',
            'traceId' => 'trace-id',
        ], $context->jsonSerialize());
    }
}
