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
use Ymir\Runtime\Application\WordPressApplication;
use Ymir\Runtime\Lambda\Handler\Http\WordPressHttpEventHandler;
use Ymir\Runtime\Lambda\Handler\PingLambdaEventHandler;
use Ymir\Runtime\Lambda\Handler\WarmUpEventHandler;
use Ymir\Runtime\RuntimeContext;
use Ymir\Runtime\Tests\Mock\FunctionMockTrait;
use Ymir\Runtime\Tests\Mock\LambdaRuntimeApiClientMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;
use Ymir\Runtime\Tests\Mock\PhpFpmProcessMockTrait;

class WordPressApplicationTest extends TestCase
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

        $application = new WordPressApplication($context);
        $handlers = $application->getWebsiteHandlers();

        $property = new \ReflectionProperty($handlers, 'handlers');
        $property->setAccessible(true);
        $handlers = $property->getValue($handlers);

        $this->assertCount(3, $handlers);
        $this->assertInstanceOf(PingLambdaEventHandler::class, $handlers[0]);
        $this->assertInstanceOf(WarmUpEventHandler::class, $handlers[1]);
        $this->assertInstanceOf(WordPressHttpEventHandler::class, $handlers[2]);
    }

    public function testPresentReturnsFalseWhenIndexPhpMissing(): void
    {
        touch($this->tempDir.'/wp-config.php');

        $this->assertFalse(WordPressApplication::present($this->tempDir));
    }

    public function testPresentReturnsFalseWhenWpConfigMissing(): void
    {
        touch($this->tempDir.'/index.php');

        $this->assertFalse(WordPressApplication::present($this->tempDir));
    }

    public function testPresentReturnsTrueWhenBothExist(): void
    {
        touch($this->tempDir.'/index.php');
        touch($this->tempDir.'/wp-config.php');

        $this->assertTrue(WordPressApplication::present($this->tempDir));
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
