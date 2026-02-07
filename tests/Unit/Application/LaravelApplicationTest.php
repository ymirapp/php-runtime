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

namespace Ymir\Runtime\Tests\Unit\Application;

use PHPUnit\Framework\TestCase;
use Ymir\Runtime\Application\LaravelApplication;
use Ymir\Runtime\Lambda\Handler\Http\LaravelHttpEventHandler;
use Ymir\Runtime\Lambda\Handler\PingLambdaEventHandler;
use Ymir\Runtime\Lambda\Handler\Sqs\LaravelSqsHandler;
use Ymir\Runtime\Lambda\Handler\WarmUpEventHandler;
use Ymir\Runtime\RuntimeContext;
use Ymir\Runtime\Tests\Mock\FunctionMockTrait;
use Ymir\Runtime\Tests\Mock\LambdaRuntimeApiClientMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;
use Ymir\Runtime\Tests\Mock\PhpFpmProcessMockTrait;

class LaravelApplicationTest extends TestCase
{
    use FunctionMockTrait;
    use LambdaRuntimeApiClientMockTrait;
    use LoggerMockTrait;
    use PhpFpmProcessMockTrait;

    private $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/ymir_test_'.uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testGetQueueHandlers(): void
    {
        $logger = $this->getLoggerMock();
        $context = new RuntimeContext($logger, $this->getLambdaRuntimeApiClientMock(), 'us-east-1', $this->tempDir);

        $application = new LaravelApplication($context);
        $handlers = $application->getQueueHandlers();

        $property = new \ReflectionProperty($handlers, 'handlers');
        $property->setAccessible(true);
        $handlers = $property->getValue($handlers);

        $this->assertCount(3, $handlers);
        $this->assertInstanceOf(PingLambdaEventHandler::class, $handlers[0]);
        $this->assertInstanceOf(WarmUpEventHandler::class, $handlers[1]);
        $this->assertInstanceOf(LaravelSqsHandler::class, $handlers[2]);
    }

    public function testGetWebsiteHandlers(): void
    {
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();
        $context = new RuntimeContext($logger, $this->getLambdaRuntimeApiClientMock(), 'us-east-1', $this->tempDir, null, $process);

        $application = new LaravelApplication($context);
        $handlers = $application->getWebsiteHandlers();

        $property = new \ReflectionProperty($handlers, 'handlers');
        $property->setAccessible(true);
        $handlers = $property->getValue($handlers);

        $this->assertCount(3, $handlers);
        $this->assertInstanceOf(PingLambdaEventHandler::class, $handlers[0]);
        $this->assertInstanceOf(WarmUpEventHandler::class, $handlers[1]);
        $this->assertInstanceOf(LaravelHttpEventHandler::class, $handlers[2]);
    }

    public function testInitializeCreatesStorageDirectoriesAndCache(): void
    {
        mkdir($this->tempDir.'/public', 0777, true);
        touch($this->tempDir.'/public/index.php');
        touch($this->tempDir.'/artisan');

        $logger = $this->getLoggerMock();
        $context = new RuntimeContext($logger, $this->getLambdaRuntimeApiClientMock(), 'us-east-1', $this->tempDir);

        $application = new LaravelApplication($context);

        try {
            $application->initialize();
        } catch (\Ymir\Runtime\Exception\ApplicationInitializationException $e) {
            // Expected failure due to missing php binary
        }

        $this->assertDirectoryExists('/tmp/storage/bootstrap/cache');
        $this->assertDirectoryExists('/tmp/storage/framework/cache');
        $this->assertDirectoryExists('/tmp/storage/framework/views');

        $this->removeDirectory('/tmp/storage');
    }

    public function testInitializeThrowsExceptionWhenProcessFails(): void
    {
        $this->expectException(\Ymir\Runtime\Exception\ApplicationInitializationException::class);
        $this->expectExceptionMessage('Failed to create Laravel cache');

        $logger = $this->getLoggerMock();
        $context = new RuntimeContext($logger, $this->getLambdaRuntimeApiClientMock(), 'us-east-1', $this->tempDir);

        $application = new LaravelApplication($context);
        $application->initialize();
    }

    public function testPresentReturnsFalseWhenFilesMissing(): void
    {
        $this->assertFalse(LaravelApplication::present($this->tempDir));
    }

    public function testPresentReturnsTrueWhenBothExist(): void
    {
        mkdir($this->tempDir.'/public', 0777, true);
        touch($this->tempDir.'/public/index.php');
        touch($this->tempDir.'/artisan');

        $this->assertTrue(LaravelApplication::present($this->tempDir));
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->removeDirectory("$dir/$file") : unlink("$dir/$file");
        }

        rmdir($dir);
    }
}
