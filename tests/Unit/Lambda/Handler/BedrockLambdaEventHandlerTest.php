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
use Tightenco\Collect\Support\Arr;
use Ymir\Runtime\FastCgi\FastCgiHttpResponse;
use Ymir\Runtime\FastCgi\FastCgiRequest;
use Ymir\Runtime\Lambda\Handler\BedrockLambdaEventHandler;
use Ymir\Runtime\Lambda\Response\NotFoundHttpResponse;
use Ymir\Runtime\Tests\Mock\HttpRequestEventMockTrait;
use Ymir\Runtime\Tests\Mock\InvocationEventInterfaceMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;
use Ymir\Runtime\Tests\Mock\PhpFpmProcessMockTrait;

/**
 * @covers \Ymir\Runtime\Lambda\Handler\BedrockLambdaEventHandler
 */
class BedrockLambdaEventHandlerTest extends TestCase
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
            $this->tempDir.'/config',
            $this->tempDir.'/tmp',
            $this->tempDir.'/web/app/mu-plugins',
            $this->tempDir.'/web/wp/tmp',
            $this->tempDir.'/web/wp/wp-admin',
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
            ['/composer.json'],
            ['/composer.lock'],
            ['/composer/installed.json'],
            ['/wp-cli.local.yml'],
            ['/wp-cli.yml'],
        ];
    }

    public function testCanHandleWithApplicationAndWpConfigPresent()
    {
        $process = $this->getPhpFpmProcessMock();

        touch($this->tempDir.'/config/application.php');
        touch($this->tempDir.'/web/wp-config.php');

        $this->assertTrue((new BedrockLambdaEventHandler($this->getLoggerMock(), $process, $this->tempDir))->canHandle($this->getHttpRequestEventMock()));

        @unlink($this->tempDir.'/config/application.php');
        @unlink($this->tempDir.'/web/wp-config.php');
    }

    public function testCanHandleWithBedrockAutoloaderPresent()
    {
        $process = $this->getPhpFpmProcessMock();

        $handler = new BedrockLambdaEventHandler($this->getLoggerMock(), $process, $this->tempDir);

        touch($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');

        $this->assertTrue($handler->canHandle($this->getHttpRequestEventMock()));

        @unlink($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
    }

    public function testCanHandleWithMissingApplicationConfig()
    {
        $process = $this->getPhpFpmProcessMock();

        touch($this->tempDir.'/web/wp-config.php');

        $this->assertFalse((new BedrockLambdaEventHandler($this->getLoggerMock(), $process, $this->tempDir))->canHandle($this->getHttpRequestEventMock()));

        @unlink($this->tempDir.'/web/wp-config.php');
    }

    public function testCanHandleWithMissingWpConfig()
    {
        $process = $this->getPhpFpmProcessMock();

        touch($this->tempDir.'/config/application.php');

        $this->assertFalse((new BedrockLambdaEventHandler($this->getLoggerMock(), $process, $this->tempDir))->canHandle($this->getHttpRequestEventMock()));

        @unlink($this->tempDir.'/config/application.php');
    }

    public function testCanHandleWithNoBedrockAutoloaderOrApplicationOrWordPressConfig()
    {
        $process = $this->getPhpFpmProcessMock();

        $this->assertFalse((new BedrockLambdaEventHandler($this->getLoggerMock(), $process, $this->tempDir))->canHandle($this->getHttpRequestEventMock()));
    }

    public function testCanHandleWrongEventType()
    {
        $process = $this->getPhpFpmProcessMock();

        $this->assertFalse((new BedrockLambdaEventHandler($this->getLoggerMock(), $process, ''))->canHandle($this->getInvocationEventInterfaceMock()));
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

        touch($this->tempDir.'/config/application.php');
        touch($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/tmp/index.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockLambdaEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/config/application.php');
        @unlink($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
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

        touch($this->tempDir.'/config/application.php');
        touch($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/tmp/index.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockLambdaEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/config/application.php');
        @unlink($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
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
                    return $request->getScriptFilename() === $this->tempDir.'/web/index.php';
                }));

        touch($this->tempDir.'/config/application.php');
        touch($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockLambdaEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/config/application.php');
        @unlink($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
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
                    return $request->getScriptFilename() === $this->tempDir.'/web/index.php';
                }));

        touch($this->tempDir.'/config/application.php');
        touch($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockLambdaEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/config/application.php');
        @unlink($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
    }

    public function testHandleCreatesFastCgiRequestToWebDirectoryWithWpPathsWithPayloadVersion1()
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
                    return $request->getScriptFilename() === $this->tempDir.'/web/wp/tmp/index.php';
                }));

        touch($this->tempDir.'/config/application.php');
        touch($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/web/wp/tmp/index.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockLambdaEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/config/application.php');
        @unlink($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/web/wp/tmp/index.php');
    }

    public function testHandleCreatesFastCgiRequestToWebDirectoryWithWpPathsWithPayloadVersion2()
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
                    return $request->getScriptFilename() === $this->tempDir.'/web/wp/tmp/index.php';
                }));

        touch($this->tempDir.'/config/application.php');
        touch($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/web/wp/tmp/index.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockLambdaEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/config/application.php');
        @unlink($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/web/wp/tmp/index.php');
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

        touch($this->tempDir.'/config/application.php');
        touch($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.$filePath);

        $this->assertInstanceOf(NotFoundHttpResponse::class, (new BedrockLambdaEventHandler($this->getLoggerMock(), $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/config/application.php');
        @unlink($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.$filePath);
    }

    public function testHandleRewritesWpAdminUrlWithPayloadVersion1()
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
                    return $request->getScriptFilename() === $this->tempDir.'/web/wp/wp-admin/index.php';
                }));

        touch($this->tempDir.'/config/application.php');
        touch($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/web/wp/wp-admin/index.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockLambdaEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/config/application.php');
        @unlink($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/web/wp/wp-admin/index.php');
    }

    public function testHandleRewritesWpAdminUrlWithPayloadVersion2()
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
                    return $request->getScriptFilename() === $this->tempDir.'/web/wp/wp-admin/index.php';
                }));

        touch($this->tempDir.'/config/application.php');
        touch($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/web/wp/wp-admin/index.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockLambdaEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/config/application.php');
        @unlink($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/web/wp/wp-admin/index.php');
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
                    return $request->getScriptFilename() === $this->tempDir.'/web/wp/wp-admin/index.php';
                }));

        touch($this->tempDir.'/config/application.php');
        touch($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/web/wp/wp-admin/index.php');

        file_put_contents($this->tempDir.'/config/application.php', 'Config::define(\'MULTISITE\', true);');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockLambdaEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/config/application.php');
        @unlink($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/web/wp/wp-admin/index.php');
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
                    return $request->getScriptFilename() === $this->tempDir.'/web/wp/wp-admin/index.php';
                }));

        touch($this->tempDir.'/config/application.php');
        touch($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/web/wp/wp-admin/index.php');

        file_put_contents($this->tempDir.'/config/application.php', 'Config::define(\'MULTISITE\', true);');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockLambdaEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/config/application.php');
        @unlink($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/web/wp/wp-admin/index.php');
    }

    public function testHandleRewritesWpLoginUrlWithPayloadVersion1()
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
                    return $request->getScriptFilename() === $this->tempDir.'/web/wp/wp-login.php';
                }));

        touch($this->tempDir.'/config/application.php');
        touch($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/web/wp/wp-login.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockLambdaEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/config/application.php');
        @unlink($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/web/wp/wp-login.php');
    }

    public function testHandleRewritesWpLoginUrlWithPayloadVersion2()
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
                    return $request->getScriptFilename() === $this->tempDir.'/web/wp/wp-login.php';
                }));

        touch($this->tempDir.'/config/application.php');
        touch($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/web/wp/wp-login.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockLambdaEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/config/application.php');
        @unlink($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/web/wp/wp-login.php');
    }

    public function testHandleRewritesWpLoginUrlWithSubdirectoryMultisiteWithPayloadVersion1()
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
                    return $request->getScriptFilename() === $this->tempDir.'/web/wp/wp-login.php';
                }));

        touch($this->tempDir.'/config/application.php');
        touch($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/web/wp/wp-login.php');

        file_put_contents($this->tempDir.'/config/application.php', 'Config::define(\'MULTISITE\', true);');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockLambdaEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/config/application.php');
        @unlink($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/web/wp/wp-login.php');
    }

    public function testHandleRewritesWpLoginUrlWithSubdirectoryMultisiteWithPayloadVersion2()
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
                    return $request->getScriptFilename() === $this->tempDir.'/web/wp/wp-login.php';
                }));

        touch($this->tempDir.'/config/application.php');
        touch($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/web/wp/wp-login.php');

        file_put_contents($this->tempDir.'/config/application.php', 'Config::define(\'MULTISITE\', true);');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockLambdaEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/config/application.php');
        @unlink($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/web/wp/wp-login.php');
    }

    public function testHandleWpLoginUrlWithPathInfoWithPayloadVersion1()
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
                    return $request->getScriptFilename() === $this->tempDir.'/web/wp/wp-login.php'
                        && '/foo' === Arr::get($request->getParams(), 'PATH_INFO');
                }));

        touch($this->tempDir.'/config/application.php');
        touch($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/web/wp/wp-login.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockLambdaEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/config/application.php');
        @unlink($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/web/wp/wp-login.php');
    }

    public function testHandleWpLoginUrlWithPathInfoWithPayloadVersion2()
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
                    return $request->getScriptFilename() === $this->tempDir.'/web/wp/wp-login.php'
                        && '/foo' === Arr::get($request->getParams(), 'PATH_INFO');
                }));

        touch($this->tempDir.'/config/application.php');
        touch($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/web/wp/wp-login.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockLambdaEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/config/application.php');
        @unlink($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/web/wp/wp-login.php');
    }

    public function testHandleWpLoginUrlWithPayloadVersion1()
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
                    return $request->getScriptFilename() === $this->tempDir.'/web/wp/wp-login.php';
                }));

        touch($this->tempDir.'/config/application.php');
        touch($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/web/wp/wp-login.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockLambdaEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/config/application.php');
        @unlink($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/web/wp/wp-login.php');
    }

    public function testHandleWpLoginUrlWithPayloadVersion2()
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
                    return $request->getScriptFilename() === $this->tempDir.'/web/wp/wp-login.php';
                }));

        touch($this->tempDir.'/config/application.php');
        touch($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
        touch($this->tempDir.'/web/wp/wp-login.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockLambdaEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/config/application.php');
        @unlink($this->tempDir.'/web/app/mu-plugins/bedrock-autoloader.php');
        @unlink($this->tempDir.'/web/wp/wp-login.php');
    }
}
