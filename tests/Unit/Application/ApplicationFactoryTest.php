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
use Ymir\Runtime\Application\ApplicationFactory;
use Ymir\Runtime\Application\BedrockApplication;
use Ymir\Runtime\Application\LaravelApplication;
use Ymir\Runtime\Application\RadicleApplication;
use Ymir\Runtime\Application\WordPressApplication;
use Ymir\Runtime\Exception\ApplicationInitializationException;
use Ymir\Runtime\RuntimeContext;
use Ymir\Runtime\Tests\Mock\FunctionMockTrait;
use Ymir\Runtime\Tests\Mock\LambdaRuntimeApiClientMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;

class ApplicationFactoryTest extends TestCase
{
    use FunctionMockTrait;
    use LambdaRuntimeApiClientMockTrait;
    use LoggerMockTrait;

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

    public function testCreateFromContextReturnsBedrockApplication(): void
    {
        mkdir($this->tempDir.'/web/app/mu-plugins', 0777, true);
        touch($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');

        $context = new RuntimeContext($this->getLoggerMock(), $this->getLambdaRuntimeApiClientMock(), 'us-east-1', $this->tempDir);

        $this->assertInstanceOf(BedrockApplication::class, ApplicationFactory::createFromContext($context));
    }

    public function testCreateFromContextReturnsLaravelApplication(): void
    {
        mkdir($this->tempDir.'/public', 0777, true);
        touch($this->tempDir.'/public/index.php');
        touch($this->tempDir.'/artisan');

        $context = new RuntimeContext($this->getLoggerMock(), $this->getLambdaRuntimeApiClientMock(), 'us-east-1', $this->tempDir);

        $this->assertInstanceOf(LaravelApplication::class, ApplicationFactory::createFromContext($context));
    }

    public function testCreateFromContextReturnsRadicleApplication(): void
    {
        mkdir($this->tempDir.'/public/content/mu-plugins', 0777, true);
        touch($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');

        $context = new RuntimeContext($this->getLoggerMock(), $this->getLambdaRuntimeApiClientMock(), 'us-east-1', $this->tempDir);

        $this->assertInstanceOf(RadicleApplication::class, ApplicationFactory::createFromContext($context));
    }

    public function testCreateFromContextReturnsWordPressApplication(): void
    {
        touch($this->tempDir.'/index.php');
        touch($this->tempDir.'/wp-config.php');

        $context = new RuntimeContext($this->getLoggerMock(), $this->getLambdaRuntimeApiClientMock(), 'us-east-1', $this->tempDir);

        $this->assertInstanceOf(WordPressApplication::class, ApplicationFactory::createFromContext($context));
    }

    public function testCreateFromContextThrowsExceptionWhenNoApplicationFound(): void
    {
        $this->expectException(ApplicationInitializationException::class);
        $this->expectExceptionMessage('Unable to create runtime application');

        $context = new RuntimeContext($this->getLoggerMock(), $this->getLambdaRuntimeApiClientMock(), 'us-east-1', $this->tempDir);

        ApplicationFactory::createFromContext($context);
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
