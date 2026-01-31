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
use Ymir\Runtime\FastCgi\PhpFpmProcess;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;

/**
 * @covers \Ymir\Runtime\FastCgi\PhpFpmProcess
 */
class PhpFpmProcessTest extends TestCase
{
    use LoggerMockTrait;

    public function testCreateForConfigWithCustomValue(): void
    {
        $logger = $this->getLoggerMock();
        $phpFpmProcess = PhpFpmProcess::createForConfig($logger, '/foo/bar');
        $reflection = new \ReflectionObject($phpFpmProcess);

        $processProperty = $reflection->getProperty('process');
        $processProperty->setAccessible(true);
        $process = $processProperty->getValue($phpFpmProcess);

        $this->assertSame("'php-fpm' '--nodaemonize' '--force-stderr' '--fpm-config' '/foo/bar'", $process->getCommandLine());
    }

    public function testCreateForConfigWithDefault(): void
    {
        $logger = $this->getLoggerMock();
        $phpFpmProcess = PhpFpmProcess::createForConfig($logger);
        $reflection = new \ReflectionObject($phpFpmProcess);

        $processProperty = $reflection->getProperty('process');
        $processProperty->setAccessible(true);
        $process = $processProperty->getValue($phpFpmProcess);

        $this->assertSame("'php-fpm' '--nodaemonize' '--force-stderr' '--fpm-config' '/opt/ymir/etc/php-fpm.d/php-fpm.conf'", $process->getCommandLine());
    }
}
