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

namespace Ymir\Runtime\Tests\Unit\Lambda\Handler\Http;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Tightenco\Collect\Support\Arr;
use Ymir\Runtime\FastCgi\FastCgiHttpResponse;
use Ymir\Runtime\FastCgi\FastCgiRequest;
use Ymir\Runtime\Lambda\Handler\Http\WordPressHttpEventHandler;
use Ymir\Runtime\Lambda\Response\NotFoundHttpResponse;
use Ymir\Runtime\Tests\Mock\HttpRequestEventMockTrait;
use Ymir\Runtime\Tests\Mock\InvocationEventInterfaceMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;
use Ymir\Runtime\Tests\Mock\PhpFpmProcessMockTrait;

/**
 * @covers \Ymir\Runtime\Lambda\Handler\Http\WordPressHttpEventHandler
 */
class WordPressHttpEventHandlerTest extends TestCase
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
        ])->each(function (string $directory): void {
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

    public function testCanHandleWithIndexAndWpConfigPresent(): void
    {
        $process = $this->getPhpFpmProcessMock();

        touch($this->tempDir.'/index.php');
        touch($this->tempDir.'/wp-config.php');

        $this->assertTrue((new WordPressHttpEventHandler($this->getLoggerMock(), $process, $this->tempDir))->canHandle($this->getHttpRequestEventMock()));

        @unlink($this->tempDir.'/index.php');
        @unlink($this->tempDir.'/wp-config.php');
    }

    public function testCanHandleWithMissingIndex(): void
    {
        $process = $this->getPhpFpmProcessMock();

        touch($this->tempDir.'/wp-config.php');

        $this->assertFalse((new WordPressHttpEventHandler($this->getLoggerMock(), $process, $this->tempDir))->canHandle($this->getHttpRequestEventMock()));

        @unlink($this->tempDir.'/wp-config.php');
    }

    public function testCanHandleWithMissingWpConfig(): void
    {
        $process = $this->getPhpFpmProcessMock();

        touch($this->tempDir.'/index.php');

        $this->assertFalse((new WordPressHttpEventHandler($this->getLoggerMock(), $process, $this->tempDir))->canHandle($this->getHttpRequestEventMock()));

        @unlink($this->tempDir.'/index.php');
    }

    public function testCanHandleWrongEventType(): void
    {
        $process = $this->getPhpFpmProcessMock();

        $this->assertFalse((new WordPressHttpEventHandler($this->getLoggerMock(), $process, ''))->canHandle($this->getInvocationEventInterfaceMock()));
    }

    public function testHandleCreatesFastCgiRequestToFolderIndexPhpIfFileExistsWithPayloadVersion1(): void
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

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new WordPressHttpEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/index.php');
        @unlink($this->tempDir.'/wp-config.php');
        @unlink($this->tempDir.'/tmp/index.php');
    }

    public function testHandleCreatesFastCgiRequestToFolderIndexPhpIfFileExistsWithPayloadVersion2(): void
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

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new WordPressHttpEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/index.php');
        @unlink($this->tempDir.'/wp-config.php');
        @unlink($this->tempDir.'/tmp/index.php');
    }

    public function testHandleCreatesFastCgiRequestToRootIndexPhpByDefaultWithPayloadVersion1(): void
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

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new WordPressHttpEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/index.php');
        @unlink($this->tempDir.'/wp-config.php');
    }

    public function testHandleCreatesFastCgiRequestToRootIndexPhpByDefaultWithPayloadVersion2(): void
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

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new WordPressHttpEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/index.php');
        @unlink($this->tempDir.'/wp-config.php');
    }

    /**
     * @dataProvider inaccessibleFilesProvider
     */
    public function testHandleReturnsNotFoundHttpResponseForInaccessibleFiles(string $filePath): void
    {
        $event = $this->getHttpRequestEventMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->exactly(1))
              ->method('getPath')
              ->willReturn($filePath);

        touch($this->tempDir.'/index.php');
        touch($this->tempDir.'/wp-config.php');
        touch($this->tempDir.$filePath);

        $this->assertInstanceOf(NotFoundHttpResponse::class, (new WordPressHttpEventHandler($this->getLoggerMock(), $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/index.php');
        @unlink($this->tempDir.'/wp-config.php');
        @unlink($this->tempDir.$filePath);
    }

    public function testHandleRewritesWpAdminUrlWithSubdirectoryMultisiteWithPayloadVersion1(): void
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

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new WordPressHttpEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/index.php');
        @unlink($this->tempDir.'/wp-config.php');
        @unlink($this->tempDir.'/wp-admin/index.php');
    }

    public function testHandleRewritesWpAdminUrlWithSubdirectoryMultisiteWithPayloadVersion2(): void
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

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new WordPressHttpEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/index.php');
        @unlink($this->tempDir.'/wp-config.php');
        @unlink($this->tempDir.'/wp-admin/index.php');
    }

    public function testHandleRewritesWpLoginUrlWithMultisiteWithPayloadVersion1(): void
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

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new WordPressHttpEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/index.php');
        @unlink($this->tempDir.'/wp-config.php');
        @unlink($this->tempDir.'/wp-login.php');
    }

    public function testHandleRewritesWpLoginUrlWithMultisiteWithPayloadVersion2(): void
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

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new WordPressHttpEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/index.php');
        @unlink($this->tempDir.'/wp-config.php');
        @unlink($this->tempDir.'/wp-login.php');
    }

    public function testHandleWpLoginUrlWithPathInfoWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->exactly(3))
              ->method('getPath')
              ->willReturn('/wp-login.php/foo');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('1.0');

        $logger->expects($this->once())
               ->method('debug');

        $process->expects($this->once())
                ->method('handle')
                ->with($this->callback(function (FastCgiRequest $request) {
                    return $request->getScriptFilename() === $this->tempDir.'/wp-login.php'
                        && '/foo' === Arr::get($request->getParams(), 'PATH_INFO');
                }));

        touch($this->tempDir.'/index.php');
        touch($this->tempDir.'/wp-config.php');
        touch($this->tempDir.'/wp-login.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new WordPressHttpEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/index.php');
        @unlink($this->tempDir.'/wp-config.php');
        @unlink($this->tempDir.'/wp-login.php');
    }

    public function testHandleWpLoginUrlWithPathInfoWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->exactly(3))
              ->method('getPath')
              ->willReturn('/wp-login.php/foo');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('2.0');

        $logger->expects($this->once())
               ->method('debug');

        $process->expects($this->once())
                ->method('handle')
                ->with($this->callback(function (FastCgiRequest $request) {
                    return $request->getScriptFilename() === $this->tempDir.'/wp-login.php'
                        && '/foo' === Arr::get($request->getParams(), 'PATH_INFO');
                }));

        touch($this->tempDir.'/index.php');
        touch($this->tempDir.'/wp-config.php');
        touch($this->tempDir.'/wp-login.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new WordPressHttpEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/index.php');
        @unlink($this->tempDir.'/wp-config.php');
        @unlink($this->tempDir.'/wp-login.php');
    }
}
