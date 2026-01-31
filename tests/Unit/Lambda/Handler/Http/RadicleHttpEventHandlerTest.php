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
use Ymir\Runtime\Lambda\Handler\Http\RadicleHttpEventHandler;
use Ymir\Runtime\Lambda\Response\NotFoundHttpResponse;
use Ymir\Runtime\Lambda\Response\StaticFileResponse;
use Ymir\Runtime\Tests\Mock\HttpRequestEventMockTrait;
use Ymir\Runtime\Tests\Mock\InvocationEventInterfaceMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;
use Ymir\Runtime\Tests\Mock\PhpFpmProcessMockTrait;

/**
 * @covers \Ymir\Runtime\Lambda\Handler\Http\RadicleHttpEventHandler
 */
class RadicleHttpEventHandlerTest extends TestCase
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
            $this->tempDir.'/composer',
            $this->tempDir.'/bedrock',
            $this->tempDir.'/tmp',
            $this->tempDir.'/public/content/mu-plugins',
            $this->tempDir.'/public/content/plugins',
            $this->tempDir.'/public/tmp',
            $this->tempDir.'/public/wp/tmp',
            $this->tempDir.'/public/wp/wp-admin',
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
            ['/composer.json'],
            ['/composer.lock'],
            ['/composer/installed.json'],
            ['/wp-cli.local.yml'],
            ['/wp-cli.yml'],
        ];
    }

    public function testCanHandleWithApplicationAndWpConfigPresent(): void
    {
        $process = $this->getPhpFpmProcessMock();

        touch($this->tempDir.'/bedrock/application.php');
        touch($this->tempDir.'/public/wp-config.php');

        $this->assertTrue((new RadicleHttpEventHandler($this->getLoggerMock(), $process, $this->tempDir))->canHandle($this->getHttpRequestEventMock()));

        @unlink($this->tempDir.'/bedrock/application.php');
        @unlink($this->tempDir.'/public/wp-config.php');
    }

    public function testCanHandleWithBedrockAutoloaderPresent(): void
    {
        $process = $this->getPhpFpmProcessMock();

        $handler = new RadicleHttpEventHandler($this->getLoggerMock(), $process, $this->tempDir);

        touch($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');

        $this->assertTrue($handler->canHandle($this->getHttpRequestEventMock()));

        @unlink($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
    }

    public function testCanHandleWithMissingApplicationConfig(): void
    {
        $process = $this->getPhpFpmProcessMock();

        touch($this->tempDir.'/public/wp-config.php');

        $this->assertFalse((new RadicleHttpEventHandler($this->getLoggerMock(), $process, $this->tempDir))->canHandle($this->getHttpRequestEventMock()));

        @unlink($this->tempDir.'/public/wp-config.php');
    }

    public function testCanHandleWithMissingWpConfig(): void
    {
        $process = $this->getPhpFpmProcessMock();

        touch($this->tempDir.'/bedrock/application.php');

        $this->assertFalse((new RadicleHttpEventHandler($this->getLoggerMock(), $process, $this->tempDir))->canHandle($this->getHttpRequestEventMock()));

        @unlink($this->tempDir.'/bedrock/application.php');
    }

    public function testCanHandleWithNoBedrockAutoloaderOrApplicationOrWordPressConfig(): void
    {
        $process = $this->getPhpFpmProcessMock();

        $this->assertFalse((new RadicleHttpEventHandler($this->getLoggerMock(), $process, $this->tempDir))->canHandle($this->getHttpRequestEventMock()));
    }

    public function testCanHandleWrongEventType(): void
    {
        $process = $this->getPhpFpmProcessMock();

        $this->assertFalse((new RadicleHttpEventHandler($this->getLoggerMock(), $process, ''))->canHandle($this->getInvocationEventInterfaceMock()));
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
                    return $request->getScriptFilename() === $this->tempDir.'/public/tmp/index.php';
                }));

        touch($this->tempDir.'/bedrock/application.php');
        touch($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/public/tmp/index.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/bedrock/application.php');
        @unlink($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/public/tmp/index.php');
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
                    return $request->getScriptFilename() === $this->tempDir.'/public/tmp/index.php';
                }));

        touch($this->tempDir.'/bedrock/application.php');
        touch($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/public/tmp/index.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/bedrock/application.php');
        @unlink($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/public/tmp/index.php');
    }

    public function testHandleCreatesFastCgiRequestToPublicDirectoryWithContentPathsWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->exactly(3))
              ->method('getPath')
              ->willReturn('content/plugins/file.php');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('1.0');

        $logger->expects($this->once())
               ->method('debug');

        $process->expects($this->once())
                ->method('handle')
                ->with($this->callback(function (FastCgiRequest $request) {
                    return $request->getScriptFilename() === $this->tempDir.'/public/content/plugins/file.php';
                }));

        touch($this->tempDir.'/bedrock/application.php');
        touch($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/public/content/plugins/file.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/bedrock/application.php');
        @unlink($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/public/content/plugins/file.php');
    }

    public function testHandleCreatesFastCgiRequestToPublicDirectoryWithContentPathsWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->exactly(3))
              ->method('getPath')
              ->willReturn('content/plugins/file.php');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('2.0');

        $logger->expects($this->once())
               ->method('debug');

        $process->expects($this->once())
                ->method('handle')
                ->with($this->callback(function (FastCgiRequest $request) {
                    return $request->getScriptFilename() === $this->tempDir.'/public/content/plugins/file.php';
                }));

        touch($this->tempDir.'/bedrock/application.php');
        touch($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/public/content/plugins/file.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/bedrock/application.php');
        @unlink($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/public/content/plugins/file.php');
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
                    return $request->getScriptFilename() === $this->tempDir.'/public/index.php';
                }));

        touch($this->tempDir.'/bedrock/application.php');
        touch($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/bedrock/application.php');
        @unlink($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
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
                    return $request->getScriptFilename() === $this->tempDir.'/public/index.php';
                }));

        touch($this->tempDir.'/bedrock/application.php');
        touch($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/bedrock/application.php');
        @unlink($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
    }

    public function testHandleCreatesFastCgiRequestToWebDirectoryWithWpPathsWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->exactly(3))
              ->method('getPath')
              ->willReturn('wp/tmp');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('1.0');

        $logger->expects($this->once())
               ->method('debug');

        $process->expects($this->once())
                ->method('handle')
                ->with($this->callback(function (FastCgiRequest $request) {
                    return $request->getScriptFilename() === $this->tempDir.'/public/wp/tmp/index.php';
                }));

        touch($this->tempDir.'/bedrock/application.php');
        touch($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/public/wp/tmp/index.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/bedrock/application.php');
        @unlink($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/public/wp/tmp/index.php');
    }

    public function testHandleCreatesFastCgiRequestToWebDirectoryWithWpPathsWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->exactly(3))
              ->method('getPath')
              ->willReturn('wp/tmp');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('2.0');

        $logger->expects($this->once())
               ->method('debug');

        $process->expects($this->once())
                ->method('handle')
                ->with($this->callback(function (FastCgiRequest $request) {
                    return $request->getScriptFilename() === $this->tempDir.'/public/wp/tmp/index.php';
                }));

        touch($this->tempDir.'/bedrock/application.php');
        touch($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/public/wp/tmp/index.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/bedrock/application.php');
        @unlink($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/public/wp/tmp/index.php');
    }

    public function testHandleDoesntReturnStaticFileResponseForFileOutsideWebDirectoryWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $logger->expects($this->once())
               ->method('debug');

        $event->expects($this->exactly(3))
              ->method('getPath')
              ->willReturn('/foo');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('1.0');

        touch($this->tempDir.'/bedrock/application.php');
        touch($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/public/wp/wp-login.php');
        touch($this->tempDir.'/foo');

        $response = (new RadicleHttpEventHandler($logger, $process, $this->tempDir))->handle($event);

        @unlink($this->tempDir.'/bedrock/application.php');
        @unlink($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/public/wp/wp-login.php');
        @unlink($this->tempDir.'/foo');

        $this->assertInstanceOf(FastCgiHttpResponse::class, $response);
        $this->assertFalse($response->isCompressible());
    }

    public function testHandleDoesntReturnStaticFileResponseForFileOutsideWebDirectoryWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $logger->expects($this->once())
               ->method('debug');

        $event->expects($this->exactly(3))
              ->method('getPath')
              ->willReturn('/foo');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('2.0');

        touch($this->tempDir.'/bedrock/application.php');
        touch($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/public/wp/wp-login.php');
        touch($this->tempDir.'/foo');

        $response = (new RadicleHttpEventHandler($logger, $process, $this->tempDir))->handle($event);

        @unlink($this->tempDir.'/bedrock/application.php');
        @unlink($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/public/wp/wp-login.php');
        @unlink($this->tempDir.'/foo');

        $this->assertInstanceOf(FastCgiHttpResponse::class, $response);
        $this->assertFalse($response->isCompressible());
    }

    public function testHandleDoesntReturnStaticFileResponseForPhpFileWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $logger->expects($this->once())
               ->method('debug');

        $event->expects($this->exactly(3))
              ->method('getPath')
              ->willReturn('/foo.php');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('1.0');

        touch($this->tempDir.'/bedrock/application.php');
        touch($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/public/wp/wp-login.php');
        touch($this->tempDir.'/public/foo.php');

        $response = (new RadicleHttpEventHandler($logger, $process, $this->tempDir))->handle($event);

        @unlink($this->tempDir.'/bedrock/application.php');
        @unlink($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/public/wp/wp-login.php');
        @unlink($this->tempDir.'/public/foo.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, $response);
        $this->assertFalse($response->isCompressible());
    }

    public function testHandleDoesntReturnStaticFileResponseForPhpFileWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();
        $logger->expects($this->once())
               ->method('debug');

        $event->expects($this->exactly(3))
              ->method('getPath')
              ->willReturn('/foo.php');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('2.0');

        touch($this->tempDir.'/bedrock/application.php');
        touch($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/public/wp/wp-login.php');
        touch($this->tempDir.'/public/foo.php');

        $response = (new RadicleHttpEventHandler($logger, $process, $this->tempDir))->handle($event);

        @unlink($this->tempDir.'/bedrock/application.php');
        @unlink($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/public/wp/wp-login.php');
        @unlink($this->tempDir.'/public/foo.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, $response);
        $this->assertFalse($response->isCompressible());
    }

    public function testHandleDoesntRewriteWpLoginUrlWithoutWpPrefixAndWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->exactly(3))
              ->method('getPath')
              ->willReturn('/wp-login.php');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('1.0');

        $logger->expects($this->once())
               ->method('debug');

        $process->expects($this->once())
                ->method('handle')
                ->with($this->callback(function (FastCgiRequest $request) {
                    return $request->getScriptFilename() === $this->tempDir.'/public/index.php';
                }));

        touch($this->tempDir.'/bedrock/application.php');
        touch($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/public/wp/wp-login.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/bedrock/application.php');
        @unlink($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/public/wp/wp-login.php');
    }

    public function testHandleDoesntRewriteWpLoginUrlWithoutWpPrefixAndWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->exactly(3))
              ->method('getPath')
              ->willReturn('/wp-login.php');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('2.0');

        $logger->expects($this->once())
               ->method('debug');

        $process->expects($this->once())
                ->method('handle')
                ->with($this->callback(function (FastCgiRequest $request) {
                    return $request->getScriptFilename() === $this->tempDir.'/public/index.php';
                }));

        touch($this->tempDir.'/bedrock/application.php');
        touch($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/public/wp/wp-login.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/bedrock/application.php');
        @unlink($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/public/wp/wp-login.php');
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

        touch($this->tempDir.'/bedrock/application.php');
        touch($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.$filePath);

        $this->assertInstanceOf(NotFoundHttpResponse::class, (new RadicleHttpEventHandler($this->getLoggerMock(), $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/bedrock/application.php');
        @unlink($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.$filePath);
    }

    public function testHandleReturnsStaticFileResponseForFileInsideWebDirectory(): void
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->once())
              ->method('getPath')
              ->willReturn('/foo');

        touch($this->tempDir.'/bedrock/application.php');
        touch($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/public/wp/wp-login.php');
        touch($this->tempDir.'/public/foo');

        $this->assertInstanceOf(StaticFileResponse::class, (new RadicleHttpEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/bedrock/application.php');
        @unlink($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/public/wp/wp-login.php');
        @unlink($this->tempDir.'/public/foo');
    }

    public function testHandleRewritesWpAdminUrlWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->exactly(3))
              ->method('getPath')
              ->willReturn('/wp-admin/');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('1.0');

        $logger->expects($this->once())
               ->method('debug');

        $process->expects($this->once())
                ->method('handle')
                ->with($this->callback(function (FastCgiRequest $request) {
                    return $request->getScriptFilename() === $this->tempDir.'/public/wp/wp-admin/index.php';
                }));

        touch($this->tempDir.'/bedrock/application.php');
        touch($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/public/wp/wp-admin/index.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/bedrock/application.php');
        @unlink($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/public/wp/wp-admin/index.php');
    }

    public function testHandleRewritesWpAdminUrlWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->exactly(3))
              ->method('getPath')
              ->willReturn('/wp-admin/');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('2.0');

        $logger->expects($this->once())
               ->method('debug');

        $process->expects($this->once())
                ->method('handle')
                ->with($this->callback(function (FastCgiRequest $request) {
                    return $request->getScriptFilename() === $this->tempDir.'/public/wp/wp-admin/index.php';
                }));

        touch($this->tempDir.'/bedrock/application.php');
        touch($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/public/wp/wp-admin/index.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/bedrock/application.php');
        @unlink($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/public/wp/wp-admin/index.php');
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
                    return $request->getScriptFilename() === $this->tempDir.'/public/wp/wp-admin/index.php';
                }));

        touch($this->tempDir.'/bedrock/application.php');
        touch($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/public/wp/wp-admin/index.php');

        file_put_contents($this->tempDir.'/bedrock/application.php', 'Config::define(\'MULTISITE\', true);');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/bedrock/application.php');
        @unlink($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/public/wp/wp-admin/index.php');
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
                    return $request->getScriptFilename() === $this->tempDir.'/public/wp/wp-admin/index.php';
                }));

        touch($this->tempDir.'/bedrock/application.php');
        touch($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/public/wp/wp-admin/index.php');

        file_put_contents($this->tempDir.'/bedrock/application.php', 'Config::define(\'MULTISITE\', true);');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/bedrock/application.php');
        @unlink($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/public/wp/wp-admin/index.php');
    }

    public function testHandleRewritesWpLoginUrlWithSubdirectoryMultisiteWithPayloadVersion1(): void
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
                    return $request->getScriptFilename() === $this->tempDir.'/public/wp/wp-login.php';
                }));

        touch($this->tempDir.'/bedrock/application.php');
        touch($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/public/wp/wp-login.php');

        file_put_contents($this->tempDir.'/bedrock/application.php', 'Config::define(\'MULTISITE\', true);');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/bedrock/application.php');
        @unlink($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/public/wp/wp-login.php');
    }

    public function testHandleRewritesWpLoginUrlWithSubdirectoryMultisiteWithPayloadVersion2(): void
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
                    return $request->getScriptFilename() === $this->tempDir.'/public/wp/wp-login.php';
                }));

        touch($this->tempDir.'/bedrock/application.php');
        touch($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/public/wp/wp-login.php');

        file_put_contents($this->tempDir.'/bedrock/application.php', 'Config::define(\'MULTISITE\', true);');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/bedrock/application.php');
        @unlink($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/public/wp/wp-login.php');
    }

    public function testHandleWpLoginUrlWithPathInfoWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->exactly(3))
              ->method('getPath')
              ->willReturn('/wp/wp-login.php/foo');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('1.0');

        $logger->expects($this->once())
               ->method('debug');

        $process->expects($this->once())
                ->method('handle')
                ->with($this->callback(function (FastCgiRequest $request) {
                    return $request->getScriptFilename() === $this->tempDir.'/public/wp/wp-login.php'
                        && '/foo' === Arr::get($request->getParams(), 'PATH_INFO');
                }));

        touch($this->tempDir.'/bedrock/application.php');
        touch($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/public/wp/wp-login.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/bedrock/application.php');
        @unlink($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/public/wp/wp-login.php');
    }

    public function testHandleWpLoginUrlWithPathInfoWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->exactly(3))
              ->method('getPath')
              ->willReturn('/wp/wp-login.php/foo');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('2.0');

        $logger->expects($this->once())
               ->method('debug');

        $process->expects($this->once())
                ->method('handle')
                ->with($this->callback(function (FastCgiRequest $request) {
                    return $request->getScriptFilename() === $this->tempDir.'/public/wp/wp-login.php'
                        && '/foo' === Arr::get($request->getParams(), 'PATH_INFO');
                }));

        touch($this->tempDir.'/bedrock/application.php');
        touch($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/public/wp/wp-login.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/bedrock/application.php');
        @unlink($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/public/wp/wp-login.php');
    }

    public function testHandleWpLoginUrlWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->exactly(3))
              ->method('getPath')
              ->willReturn('/wp/wp-login.php');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('1.0');

        $logger->expects($this->once())
               ->method('debug');

        $process->expects($this->once())
                ->method('handle')
                ->with($this->callback(function (FastCgiRequest $request) {
                    return $request->getScriptFilename() === $this->tempDir.'/public/wp/wp-login.php';
                }));

        touch($this->tempDir.'/bedrock/application.php');
        touch($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/public/wp/wp-login.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/bedrock/application.php');
        @unlink($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/public/wp/wp-login.php');
    }

    public function testHandleWpLoginUrlWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->exactly(3))
              ->method('getPath')
              ->willReturn('/wp/wp-login.php');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('2.0');

        $logger->expects($this->once())
               ->method('debug');

        $process->expects($this->once())
                ->method('handle')
                ->with($this->callback(function (FastCgiRequest $request) {
                    return $request->getScriptFilename() === $this->tempDir.'/public/wp/wp-login.php';
                }));

        touch($this->tempDir.'/bedrock/application.php');
        touch($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/public/wp/wp-login.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/bedrock/application.php');
        @unlink($this->tempDir.'/public/content/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/public/wp/wp-login.php');
    }
}
