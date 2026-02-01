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
use Ymir\Runtime\FastCgi\FastCgiHttpResponse;
use Ymir\Runtime\FastCgi\FastCgiRequest;
use Ymir\Runtime\Lambda\Handler\Http\LaravelHttpEventHandler;
use Ymir\Runtime\Lambda\Response\NotFoundHttpResponse;
use Ymir\Runtime\Lambda\Response\StaticFileResponse;
use Ymir\Runtime\Tests\Mock\HttpRequestEventMockTrait;
use Ymir\Runtime\Tests\Mock\InvocationEventInterfaceMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;
use Ymir\Runtime\Tests\Mock\PhpFpmProcessMockTrait;

class LaravelHttpEventHandlerTest extends TestCase
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
            $this->tempDir.'/public',
            $this->tempDir.'/storage/app/public',
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
            ['/package.json'],
            ['/package-lock.json'],
            ['/yarn.lock'],
        ];
    }

    public function testCanHandleWithMissingArtisan(): void
    {
        touch($this->tempDir.'/public/index.php');

        $this->assertFalse((new LaravelHttpEventHandler($this->getLoggerMock(), $this->getPhpFpmProcessMock(), $this->tempDir))->canHandle($this->getHttpRequestEventMock()));

        @unlink($this->tempDir.'/public/index.php');
    }

    public function testCanHandleWithMissingPublicIndex(): void
    {
        touch($this->tempDir.'/artisan');

        $this->assertFalse((new LaravelHttpEventHandler($this->getLoggerMock(), $this->getPhpFpmProcessMock(), $this->tempDir))->canHandle($this->getHttpRequestEventMock()));

        @unlink($this->tempDir.'/artisan');
    }

    public function testCanHandleWithPublicIndexAndArtisanPresent(): void
    {
        touch($this->tempDir.'/public/index.php');
        touch($this->tempDir.'/artisan');

        $this->assertTrue((new LaravelHttpEventHandler($this->getLoggerMock(), $this->getPhpFpmProcessMock(), $this->tempDir))->canHandle($this->getHttpRequestEventMock()));

        @unlink($this->tempDir.'/public/index.php');
        @unlink($this->tempDir.'/artisan');
    }

    public function testHandleCreatesFastCgiRequestToPublicIndexPhpByDefault(): void
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->any())
              ->method('getPath')
              ->willReturn('/foo');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('2.0');

        $process->expects($this->once())
                ->method('handle')
                ->with($this->callback(function (FastCgiRequest $request) {
                    return $request->getScriptFilename() === $this->tempDir.'/public/index.php';
                }));

        touch($this->tempDir.'/public/index.php');
        touch($this->tempDir.'/artisan');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new LaravelHttpEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/public/index.php');
        @unlink($this->tempDir.'/artisan');
    }

    public function testHandleCreatesFastCgiRequestToSpecificPhpFileIfItExists(): void
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->any())
              ->method('getPath')
              ->willReturn('/test.php');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('2.0');

        $process->expects($this->once())
                ->method('handle')
                ->with($this->callback(function (FastCgiRequest $request) {
                    return $request->getScriptFilename() === $this->tempDir.'/public/test.php';
                }));

        touch($this->tempDir.'/public/index.php');
        touch($this->tempDir.'/artisan');
        touch($this->tempDir.'/public/test.php');

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new LaravelHttpEventHandler($logger, $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/public/index.php');
        @unlink($this->tempDir.'/artisan');
        @unlink($this->tempDir.'/public/test.php');
    }

    public function testHandlePrioritizesPublicOverStorage(): void
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->any())
              ->method('getPath')
              ->willReturn('/storage/image.jpg');

        touch($this->tempDir.'/public/index.php');
        touch($this->tempDir.'/artisan');
        mkdir($this->tempDir.'/public/storage', 0777, true);
        touch($this->tempDir.'/public/storage/image.jpg');
        touch($this->tempDir.'/storage/app/public/image.jpg');

        $handler = new LaravelHttpEventHandler($logger, $process, $this->tempDir);
        $response = $handler->handle($event);

        $this->assertInstanceOf(StaticFileResponse::class, $response);

        @unlink($this->tempDir.'/public/index.php');
        @unlink($this->tempDir.'/artisan');
        @unlink($this->tempDir.'/public/storage/image.jpg');
        @unlink($this->tempDir.'/storage/app/public/image.jpg');
        @rmdir($this->tempDir.'/public/storage');
    }

    public function testHandleResolvesStoragePathIfFileDoesNotExistInPublic(): void
    {
        $event = $this->getHttpRequestEventMock();
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->any())
              ->method('getPath')
              ->willReturn('/storage/image.jpg');

        touch($this->tempDir.'/public/index.php');
        touch($this->tempDir.'/artisan');
        touch($this->tempDir.'/storage/app/public/image.jpg');

        $handler = new LaravelHttpEventHandler($logger, $process, $this->tempDir);
        $response = $handler->handle($event);

        $this->assertInstanceOf(StaticFileResponse::class, $response);

        @unlink($this->tempDir.'/public/index.php');
        @unlink($this->tempDir.'/artisan');
        @unlink($this->tempDir.'/storage/app/public/image.jpg');
    }

    /**
     * @dataProvider inaccessibleFilesProvider
     */
    public function testHandleReturnsNotFoundHttpResponseForInaccessibleFiles(string $filePath): void
    {
        $event = $this->getHttpRequestEventMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->any())
              ->method('getPath')
              ->willReturn($filePath);

        touch($this->tempDir.'/public/index.php');
        touch($this->tempDir.'/artisan');
        touch($this->tempDir.'/public'.$filePath);

        $this->assertInstanceOf(NotFoundHttpResponse::class, (new LaravelHttpEventHandler($this->getLoggerMock(), $process, $this->tempDir))->handle($event));

        @unlink($this->tempDir.'/public/index.php');
        @unlink($this->tempDir.'/artisan');
        @unlink($this->tempDir.'/public'.$filePath);
    }
}
