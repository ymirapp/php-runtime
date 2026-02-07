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
use Ymir\Runtime\Application\RadicleApplication;
use Ymir\Runtime\Lambda\Handler\Http\RadicleHttpEventHandler;
use Ymir\Runtime\Lambda\Handler\PingLambdaEventHandler;
use Ymir\Runtime\Lambda\Handler\WarmUpEventHandler;
use Ymir\Runtime\RuntimeContext;
use Ymir\Runtime\Tests\Mock\FunctionMockTrait;
use Ymir\Runtime\Tests\Mock\LambdaRuntimeApiClientMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;
use Ymir\Runtime\Tests\Mock\PhpFpmProcessMockTrait;

class RadicleApplicationTest extends TestCase
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

    public function testGetWebsiteHandlers(): void
    {
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();
        $context = new RuntimeContext($logger, $this->getLambdaRuntimeApiClientMock(), 'us-east-1', $this->tempDir, null, $process);

        $application = new RadicleApplication($context);
        $handlers = $application->getWebsiteHandlers();

        $property = new \ReflectionProperty($handlers, 'handlers');
        $property->setAccessible(true);
        $handlers = $property->getValue($handlers);

        $this->assertCount(3, $handlers);
        $this->assertInstanceOf(PingLambdaEventHandler::class, $handlers[0]);
        $this->assertInstanceOf(WarmUpEventHandler::class, $handlers[1]);
        $this->assertInstanceOf(RadicleHttpEventHandler::class, $handlers[2]);
    }

    public function testInitializeCreatesStorageDirectoriesAndCache(): void
    {
        mkdir($this->tempDir.'/public', 0777, true);
        mkdir($this->tempDir.'/bin', 0777, true);
        touch($this->tempDir.'/public/index.php');
        touch($this->tempDir.'/bin/wp');

        $logger = $this->getLoggerMock();
        $context = new RuntimeContext($logger, $this->getLambdaRuntimeApiClientMock(), 'us-east-1', $this->tempDir);

        $application = new RadicleApplication($context);

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
        $this->expectExceptionMessage('Failed to create Acorn cache');

        $logger = $this->getLoggerMock();
        $context = new RuntimeContext($logger, $this->getLambdaRuntimeApiClientMock(), 'us-east-1', $this->tempDir);

        $application = new RadicleApplication($context);
        $application->initialize();
    }

    public function testPresentReturnsFalseWhenFilesMissing(): void
    {
        $this->assertFalse(RadicleApplication::present($this->tempDir));
    }

    public function testPresentReturnsTrueWhenAutoloaderExists(): void
    {
        mkdir($this->tempDir.'/public/content/mu-plugins', 0777, true);
        touch($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');

        $this->assertTrue(RadicleApplication::present($this->tempDir));
    }

    public function testPresentReturnsTrueWhenConfigAndApplicationExists(): void
    {
        mkdir($this->tempDir.'/public', 0777, true);
        mkdir($this->tempDir.'/bedrock', 0777, true);
        touch($this->tempDir.'/public/wp-config.php');
        touch($this->tempDir.'/bedrock/application.php');

        $this->assertTrue(RadicleApplication::present($this->tempDir));
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
