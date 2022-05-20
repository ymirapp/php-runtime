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

namespace Ymir\Runtime\Tests\Unit\Lambda\Handler;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Ymir\Runtime\FastCgi\FastCgiHttpResponse;
use Ymir\Runtime\FastCgi\FastCgiRequest;
use Ymir\Runtime\Lambda\Handler\WordPressLambdaEventHandler;
use Ymir\Runtime\Lambda\Response\NotFoundHttpResponse;
use Ymir\Runtime\Tests\Mock\HttpRequestEventMockTrait;
use Ymir\Runtime\Tests\Mock\InvocationEventInterfaceMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;
use Ymir\Runtime\Tests\Mock\PhpFpmProcessMockTrait;

/**
 * @covers \Ymir\Runtime\Lambda\Handler\WordPressLambdaEventHandler
 */
class WordPressLambdaEventHandlerTest extends TestCase
{
    use HttpRequestEventMockTrait;
    use InvocationEventInterfaceMockTrait;
    use LoggerMockTrait;
    use PhpFpmProcessMockTrait;

    /**
     * @var string
     */
    private $tempDir;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir();

        collect([
            $this->tempDir.'/tmp',
            $this->tempDir.'/wp-admin',
        ])->each(function (string $directory) {
            $filesystem = new Filesystem();

            if ($filesystem->exists($directory)) {
                $filesystem->remove($directory);
            }

            $filesystem->mkdir($directory);
        });
    }

    public function inaccessibleFilesProvider(): array
    {
        return [
            ['/wp-config.php'],
            ['/readme.html'],
            ['/license.txt'],
            ['/wp-cli.local.yml'],
            ['/wp-cli.yml'],
        ];
    }

    public function testCanHandleWithIndexAndWpConfigPresent()
    {
        $process = $this->getPhpFpmProcessMock();

        touch($this->tempDir.'/index.php');
        touch($this->tempDir.'/wp-config.php');

        $this->assertTrue((new WordPressLambdaEventHandler($this->getLoggerMock(), $process, $this->tempDir))->canHandle($this->getHttpRequestEventMock()));

        @unlink($this->tempDir.'/index.php');
        @unlink($this->tempDir.'/wp-config.php');
    }

    public function testCanHandleWithMissingIndex()
    {
        $process = $this->getPhpFpmProcessMock();

        touch($this->tempDir.'/wp-config.php');

        $this->assertFalse((new WordPressLambdaEventHandler($this->getLoggerMock(), $process, $this->tempDir))->canHandle($this->getHttpRequestEventMock()));

        @unlink($this->tempDir.'/wp-config.php');
    }

    public function testCanHandleWithMissingWpConfig()
    {
        $process = $this->getPhpFpmProcessMock();

        touch($this->tempDir.'/index.php');

        $this->assertFalse((new WordPressLambdaEventHandler($this->getLoggerMock(), $process, $this->tempDir))->canHandle($this->getHttpRequestEventMock()));

        @unlink($this->tempDir.'/index.php');
    }

    public function testCanHandleWrongEventType()
    {
        $process = $this->getPhpFpmProcessMock();

        $this->assertFalse((new WordPressLambdaEventHandler($this->getLoggerMock(), $process, ''))->canHandle($this->getInvocationEventInterfaceMock()));
    }

    public function testHandleCreatesFastCgiRequestToFolderIndexPhpIfFileExistsWithPayloadVersion1()
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->exactly(3))
              ->method('getPath')
              ->willReturn('tmp/');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('1.0');

        $logger->expects($this->once())
               ->method('debug');

        $process->expects($this->once())
                ->method('handle')
                ->with($this->callback(function (FastCgiRequest $request) {
                    return $request->getScriptFilename() === $this->tempDir.'/tmp/index.php';
                }));

        touch($this->tempDir.'/index.php');
        touch($this->tempDir.'/wp-config.php');
        touch($this->tempDir.'/tmp/index.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new WordPressLambdaEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/index.php');
        @unlink($this->tempDir.'/wp-config.php');
        @unlink($this->tempDir.'/tmp/index.php');
    }

    public function testHandleCreatesFastCgiRequestToFolderIndexPhpIfFileExistsWithPayloadVersion2()
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->exactly(3))
              ->method('getPath')
              ->willReturn('tmp/');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('2.0');

        $logger->expects($this->once())
            ->method('debug');

        $process->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (FastCgiRequest $request) {
                return $request->getScriptFilename() === $this->tempDir.'/tmp/index.php';
            }));

        touch($this->tempDir.'/index.php');
        touch($this->tempDir.'/wp-config.php');
        touch($this->tempDir.'/tmp/index.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new WordPressLambdaEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/index.php');
        @unlink($this->tempDir.'/wp-config.php');
        @unlink($this->tempDir.'/tmp/index.php');
    }

    public function testHandleCreatesFastCgiRequestToRootIndexPhpByDefaultWithPayloadVersion1()
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->exactly(3))
              ->method('getPath')
              ->willReturn('tmp');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('1.0');

        $logger->expects($this->once())
               ->method('debug');

        $process->expects($this->once())
                ->method('handle')
                ->with($this->callback(function (FastCgiRequest $request) {
                    return $request->getScriptFilename() === $this->tempDir.'/index.php';
                }));

        touch($this->tempDir.'/index.php');
        touch($this->tempDir.'/wp-config.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new WordPressLambdaEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/index.php');
        @unlink($this->tempDir.'/wp-config.php');
    }

    public function testHandleCreatesFastCgiRequestToRootIndexPhpByDefaultWithPayloadVersion2()
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->exactly(3))
              ->method('getPath')
              ->willReturn('tmp');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('2.0');

        $logger->expects($this->once())
               ->method('debug');

        $process->expects($this->once())
                ->method('handle')
                ->with($this->callback(function (FastCgiRequest $request) {
                    return $request->getScriptFilename() === $this->tempDir.'/index.php';
                }));

        touch($this->tempDir.'/index.php');
        touch($this->tempDir.'/wp-config.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new WordPressLambdaEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/index.php');
        @unlink($this->tempDir.'/wp-config.php');
    }

    /**
     * @dataProvider inaccessibleFilesProvider
     */
    public function testHandleReturnsNotFoundHttpResponseForInaccessibleFiles(string $filePath)
    {
        $event = $this->getHttpRequestEventMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->exactly(1))
              ->method('getPath')
              ->willReturn($filePath);

        touch($this->tempDir.'/index.php');
        touch($this->tempDir.'/wp-config.php');
        touch($this->tempDir.$filePath);

        $this->assertInstanceOf(NotFoundHttpResponse::class, (new WordPressLambdaEventHandler($this->getLoggerMock(), $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/index.php');
        @unlink($this->tempDir.'/wp-config.php');
        @unlink($this->tempDir.$filePath);
    }

    public function testHandleRewritesWpAdminUrlWithSubdirectoryMultisiteWithPayloadVersion1()
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->exactly(3))
              ->method('getPath')
              ->willReturn('/subdirectory/wp-admin/');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('1.0');

        $logger->expects($this->once())
               ->method('debug');

        $process->expects($this->once())
                ->method('handle')
                ->with($this->callback(function (FastCgiRequest $request) {
                    return $request->getScriptFilename() === $this->tempDir.'/wp-admin/index.php';
                }));

        touch($this->tempDir.'/index.php');
        touch($this->tempDir.'/wp-config.php');
        touch($this->tempDir.'/wp-admin/index.php');

        file_put_contents($this->tempDir.'/wp-config.php', 'define(\'MULTISITE\', true);');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new WordPressLambdaEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/index.php');
        @unlink($this->tempDir.'/wp-config.php');
        @unlink($this->tempDir.'/wp-admin/index.php');
    }

    public function testHandleRewritesWpAdminUrlWithSubdirectoryMultisiteWithPayloadVersion2()
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->exactly(3))
              ->method('getPath')
              ->willReturn('/subdirectory/wp-admin/');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('2.0');

        $logger->expects($this->once())
               ->method('debug');

        $process->expects($this->once())
                ->method('handle')
                ->with($this->callback(function (FastCgiRequest $request) {
                    return $request->getScriptFilename() === $this->tempDir.'/wp-admin/index.php';
                }));

        touch($this->tempDir.'/index.php');
        touch($this->tempDir.'/wp-config.php');
        touch($this->tempDir.'/wp-admin/index.php');

        file_put_contents($this->tempDir.'/wp-config.php', 'define(\'MULTISITE\', true);');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new WordPressLambdaEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/index.php');
        @unlink($this->tempDir.'/wp-config.php');
        @unlink($this->tempDir.'/wp-admin/index.php');
    }

    public function testHandleRewritesWpLoginUrlWithMultisiteWithPayloadVersion1()
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->exactly(3))
              ->method('getPath')
              ->willReturn('/subdirectory/wp-login.php');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('1.0');

        $logger->expects($this->once())
               ->method('debug');

        $process->expects($this->once())
                ->method('handle')
                ->with($this->callback(function (FastCgiRequest $request) {
                    return $request->getScriptFilename() === $this->tempDir.'/wp-login.php';
                }));

        touch($this->tempDir.'/index.php');
        touch($this->tempDir.'/wp-config.php');
        touch($this->tempDir.'/wp-login.php');

        file_put_contents($this->tempDir.'/wp-config.php', 'define(\'MULTISITE\', true);');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new WordPressLambdaEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/index.php');
        @unlink($this->tempDir.'/wp-config.php');
        @unlink($this->tempDir.'/wp-login.php');
    }

    public function testHandleRewritesWpLoginUrlWithMultisiteWithPayloadVersion2()
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->exactly(3))
              ->method('getPath')
              ->willReturn('/subdirectory/wp-login.php');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('2.0');

        $logger->expects($this->once())
               ->method('debug');

        $process->expects($this->once())
                ->method('handle')
                ->with($this->callback(function (FastCgiRequest $request) {
                    return $request->getScriptFilename() === $this->tempDir.'/wp-login.php';
                }));

        touch($this->tempDir.'/index.php');
        touch($this->tempDir.'/wp-config.php');
        touch($this->tempDir.'/wp-login.php');

        file_put_contents($this->tempDir.'/wp-config.php', 'define(\'MULTISITE\', true);');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new WordPressLambdaEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/index.php');
        @unlink($this->tempDir.'/wp-config.php');
        @unlink($this->tempDir.'/wp-login.php');
    }
}
