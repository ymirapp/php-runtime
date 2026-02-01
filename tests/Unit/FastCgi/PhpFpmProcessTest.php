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

namespace Ymir\Runtime\Tests\Unit\FastCgi;

use PHPUnit\Framework\TestCase;
use Ymir\Runtime\Exception\PhpFpm\PhpFpmProcessException;
use Ymir\Runtime\Exception\PhpFpm\PhpFpmTimeoutException;
use Ymir\Runtime\FastCgi\PhpFpmProcess;
use Ymir\Runtime\Tests\Mock\FastCgiServerClientMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;
use Ymir\Runtime\Tests\Mock\ProcessMockTrait;
use Ymir\Runtime\Tests\Mock\ProvidesRequestDataMockTrait;
use Ymir\Runtime\Tests\Mock\ProvidesResponseDataMockTrait;

class PhpFpmProcessTest extends TestCase
{
    use FastCgiServerClientMockTrait;
    use LoggerMockTrait;
    use ProcessMockTrait;
    use ProvidesRequestDataMockTrait;
    use ProvidesResponseDataMockTrait;

    public function testCreateForConfigWithCustomValue(): void
    {
        $logger = $this->getLoggerMock();
        $phpFpmProcess = PhpFpmProcess::createForConfig($logger, '/foo/bar');
        $reflection = new \ReflectionObject($phpFpmProcess);

        $processProperty = $reflection->getProperty('process');
        $processProperty->setAccessible(true);
        $process = $processProperty->getValue($phpFpmProcess);

        $this->assertSame("'php-fpm' '--nodaemonize' '--force-stderr' '--fpm-config' '/foo/bar' '-d' 'opcache.file_cache_only=0'", $process->getCommandLine());
    }

    public function testCreateForConfigWithDefault(): void
    {
        $logger = $this->getLoggerMock();
        $phpFpmProcess = PhpFpmProcess::createForConfig($logger);
        $reflection = new \ReflectionObject($phpFpmProcess);

        $processProperty = $reflection->getProperty('process');
        $processProperty->setAccessible(true);
        $process = $processProperty->getValue($phpFpmProcess);

        $this->assertSame("'php-fpm' '--nodaemonize' '--force-stderr' '--fpm-config' '/opt/ymir/etc/php-fpm.d/php-fpm.conf' '-d' 'opcache.file_cache_only=0'", $process->getCommandLine());
    }

    public function testHandle(): void
    {
        $client = $this->getFastCgiServerClientMock();
        $logger = $this->getLoggerMock();
        $process = $this->getProcessMock();
        $request = $this->getProvidesRequestDataMock();
        $response = $this->getProvidesResponseDataMock();

        $client->expects($this->once())
               ->method('handle')
               ->with($this->identicalTo($request), 1000)
               ->willReturn($response);

        $process->method('isRunning')
                ->willReturn(true);

        $phpFpmProcess = new PhpFpmProcess($client, $logger, $process);

        $this->assertSame($response, $phpFpmProcess->handle($request, 1000));
    }

    public function testHandleWithProcessStopped(): void
    {
        $this->expectException(PhpFpmProcessException::class);
        $this->expectExceptionMessage('PHP-FPM has stopped unexpectedly');

        $client = $this->getFastCgiServerClientMock();
        $logger = $this->getLoggerMock();
        $process = $this->getProcessMock();
        $request = $this->getProvidesRequestDataMock();
        $response = $this->getProvidesResponseDataMock();

        $client->expects($this->once())
               ->method('handle')
               ->with($this->identicalTo($request), 1000)
               ->willReturn($response);

        $process->method('isRunning')
                ->willReturn(false);

        $phpFpmProcess = new PhpFpmProcess($client, $logger, $process);

        $phpFpmProcess->handle($request, 1000);
    }

    public function testHandleWithReadFailedException(): void
    {
        $this->expectException(PhpFpmProcessException::class);
        $this->expectExceptionMessage('PHP-FPM process crashed unexpectedly');

        $client = $this->getFastCgiServerClientMock();
        $logger = $this->getLoggerMock();
        $process = $this->getProcessMock();
        $request = $this->getProvidesRequestDataMock();

        $client->expects($this->once())
               ->method('handle')
               ->with($this->identicalTo($request), 1000)
               ->willThrowException(new \hollodotme\FastCGI\Exceptions\ReadFailedException());

        $phpFpmProcess = new PhpFpmProcess($client, $logger, $process);

        $phpFpmProcess->handle($request, 1000);
    }

    public function testHandleWithTimeout(): void
    {
        $this->expectException(PhpFpmTimeoutException::class);
        $this->expectExceptionMessage('PHP-FPM request timed out after 1000ms');

        $client = $this->getFastCgiServerClientMock();
        $logger = $this->getLoggerMock();
        $process = $this->getProcessMock();
        $request = $this->getProvidesRequestDataMock();

        $client->expects($this->once())
               ->method('handle')
               ->with($this->identicalTo($request), 1000)
               ->willThrowException(new \hollodotme\FastCGI\Exceptions\TimedoutException());

        $logger->expects($this->once())
               ->method('info')
               ->with('PHP-FPM request timed out after 1000ms, restarting process');

        $phpFpmProcess = $this->getMockBuilder(PhpFpmProcess::class)
                              ->setConstructorArgs([$client, $logger, $process])
                              ->setMethods(['start', 'stop'])
                              ->getMock();

        $phpFpmProcess->expects($this->once())
                      ->method('stop');
        $phpFpmProcess->expects($this->once())
                      ->method('start');

        $phpFpmProcess->handle($request, 1000);
    }
}
