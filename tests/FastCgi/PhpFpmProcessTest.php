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

namespace Placeholder\Runtime\Tests\FastCgi;

use PHPUnit\Framework\TestCase;
use Placeholder\Runtime\FastCgi\PhpFpmProcess;

/**
 * @covers \Placeholder\Runtime\FastCgi\PhpFpmProcess
 */
class PhpFpmProcessTest extends TestCase
{
    public function testCreateForConfigWithDefault()
    {
        $phpFpmProcess = PhpFpmProcess::createForConfig();
        $reflection = new \ReflectionObject($phpFpmProcess);

        $processProperty = $reflection->getProperty('process');
        $processProperty->setAccessible(true);
        $process = $processProperty->getValue($phpFpmProcess);

        $this->assertSame("'php-fpm' '--nodaemonize' '--force-stderr' '--fpm-config' '/opt/placeholder/etc/php-fpm.d/php-fpm.conf'", $process->getCommandLine());
    }

    public function testCreateForConfigWithCustomValue()
    {
        $phpFpmProcess = PhpFpmProcess::createForConfig('/foo/bar');
        $reflection = new \ReflectionObject($phpFpmProcess);

        $processProperty = $reflection->getProperty('process');
        $processProperty->setAccessible(true);
        $process = $processProperty->getValue($phpFpmProcess);

        $this->assertSame("'php-fpm' '--nodaemonize' '--force-stderr' '--fpm-config' '/foo/bar'", $process->getCommandLine());
    }
}
