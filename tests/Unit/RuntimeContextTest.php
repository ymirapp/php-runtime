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

namespace Ymir\Runtime\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ymir\Runtime\Exception\InvalidConfigurationException;
use Ymir\Runtime\FastCgi\PhpFpmProcess;
use Ymir\Runtime\Logger;
use Ymir\Runtime\RuntimeApiClient;
use Ymir\Runtime\RuntimeContext;
use Ymir\Runtime\Tests\Mock\FunctionMockTrait;
use Ymir\Runtime\Tests\Mock\LambdaRuntimeApiClientMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;
use Ymir\Runtime\Tests\Mock\PhpFpmProcessMockTrait;

class RuntimeContextTest extends TestCase
{
    use FunctionMockTrait;
    use LambdaRuntimeApiClientMockTrait;
    use LoggerMockTrait;
    use PhpFpmProcessMockTrait;

    public function testCreateFromEnvironment(): void
    {
        $this->getFunctionMock($this->getNamespace(RuntimeContext::class), 'getenv')
             ->expects($this->any())
             ->willReturnCallback(function ($name) {
                 switch ($name) {
                     case 'AWS_REGION':
                         return 'us-east-1';
                     case 'LAMBDA_TASK_ROOT':
                         return '/var/task';
                     case 'AWS_LAMBDA_RUNTIME_API':
                         return '127.0.0.1:8080';
                     case 'YMIR_RUNTIME_LOG_LEVEL':
                         return 'debug';
                     case 'YMIR_RUNTIME_MAX_INVOCATIONS':
                         return '100';
                 }

                 return false;
             });

        $handle = curl_init();
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_init')
             ->expects($this->any())
             ->willReturn($handle);
        $this->getFunctionMock($this->getNamespace(RuntimeApiClient::class), 'curl_setopt')
             ->expects($this->any())
             ->willReturn(true);

        $context = RuntimeContext::createFromEnvironment();

        $this->assertSame('us-east-1', $context->getRegion());
        $this->assertSame('/var/task', $context->getRootDirectory());
        $this->assertSame(100, $context->getMaxInvocations());
        $this->assertInstanceOf(Logger::class, $context->getLogger());

        curl_close($handle);
    }

    public function testCreateFromEnvironmentThrowsExceptionIfAwsLambdaRuntimeApiIsMissing(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The "AWS_LAMBDA_RUNTIME_API" environment variable is missing');

        $this->getFunctionMock($this->getNamespace(RuntimeContext::class), 'getenv')
             ->expects($this->any())
             ->willReturnCallback(function ($name) {
                 if ('AWS_LAMBDA_RUNTIME_API' === $name) {
                     return false;
                 }

                 return 'YMIR_RUNTIME_LOG_LEVEL' === $name ? 'info' : 'foo';
             });

        RuntimeContext::createFromEnvironment();
    }

    public function testCreateFromEnvironmentThrowsExceptionIfAwsRegionIsMissing(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The "AWS_REGION" environment variable is missing');

        $this->getFunctionMock($this->getNamespace(RuntimeContext::class), 'getenv')
             ->expects($this->any())
             ->willReturnCallback(function ($name) {
                 if ('AWS_REGION' === $name) {
                     return false;
                 }

                 return 'YMIR_RUNTIME_LOG_LEVEL' === $name ? 'info' : 'foo';
             });

        RuntimeContext::createFromEnvironment();
    }

    public function testCreateFromEnvironmentThrowsExceptionIfLambdaTaskRootIsMissing(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The "LAMBDA_TASK_ROOT" environment variable is missing');

        $this->getFunctionMock($this->getNamespace(RuntimeContext::class), 'getenv')
             ->expects($this->any())
             ->willReturnCallback(function ($name) {
                 if ('LAMBDA_TASK_ROOT' === $name) {
                     return false;
                 }

                 return 'YMIR_RUNTIME_LOG_LEVEL' === $name ? 'info' : 'foo';
             });

        RuntimeContext::createFromEnvironment();
    }

    public function testGetLogger(): void
    {
        $logger = $this->getLoggerMock();

        $context = new RuntimeContext($logger, $this->getLambdaRuntimeApiClientMock(), 'us-east-1', '/var/task');

        $this->assertSame($logger, $context->getLogger());
    }

    public function testGetMaxInvocations(): void
    {
        $context = new RuntimeContext($this->getLoggerMock(), $this->getLambdaRuntimeApiClientMock(), 'us-east-1', '/var/task', 100);

        $this->assertSame(100, $context->getMaxInvocations());
    }

    public function testGetPhpFpmProcess(): void
    {
        $phpFpmProcess = $this->getPhpFpmProcessMock();

        $context = new RuntimeContext($this->getLoggerMock(), $this->getLambdaRuntimeApiClientMock(), 'us-east-1', '/var/task', null, $phpFpmProcess);

        $this->assertSame($phpFpmProcess, $context->getPhpFpmProcess());
    }

    public function testGetPhpFpmProcessCreatesProcessIfMissing(): void
    {
        $logger = $this->getLoggerMock();
        $runtimeApiClient = $this->getLambdaRuntimeApiClientMock();

        $context = new RuntimeContext($logger, $runtimeApiClient, 'region', 'root');

        $process = $context->getPhpFpmProcess();

        $this->assertInstanceOf(PhpFpmProcess::class, $process);
        $this->assertSame($process, $context->getPhpFpmProcess());
    }

    public function testGetRegion(): void
    {
        $context = new RuntimeContext($this->getLoggerMock(), $this->getLambdaRuntimeApiClientMock(), 'us-east-1', '/var/task');

        $this->assertSame('us-east-1', $context->getRegion());
    }

    public function testGetRootDirectory(): void
    {
        $context = new RuntimeContext($this->getLoggerMock(), $this->getLambdaRuntimeApiClientMock(), 'us-east-1', '/var/task');

        $this->assertSame('/var/task', $context->getRootDirectory());
    }

    public function testGetRuntimeApiClient(): void
    {
        $runtimeApiClient = $this->getLambdaRuntimeApiClientMock();

        $context = new RuntimeContext($this->getLoggerMock(), $runtimeApiClient, 'us-east-1', '/var/task');

        $this->assertSame($runtimeApiClient, $context->getRuntimeApiClient());
    }
}
