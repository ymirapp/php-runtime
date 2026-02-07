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
use Ymir\Runtime\FastCgi\FastCgiRequest;
use Ymir\Runtime\Lambda\Handler\Http\AbstractHttpRequestEventHandler;
use Ymir\Runtime\Lambda\Handler\Http\LaravelHttpEventHandler;
use Ymir\Runtime\Lambda\Response\Http\FastCgiHttpResponse;
use Ymir\Runtime\Lambda\Response\Http\NotFoundHttpResponse;
use Ymir\Runtime\Lambda\Response\Http\StaticFileHttpResponse;
use Ymir\Runtime\Tests\Mock\FunctionMockTrait;
use Ymir\Runtime\Tests\Mock\HttpRequestEventMockTrait;
use Ymir\Runtime\Tests\Mock\InvocationEventInterfaceMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;
use Ymir\Runtime\Tests\Mock\PhpFpmProcessMockTrait;

class LaravelHttpEventHandlerTest extends TestCase
{
    use FunctionMockTrait;
    use HttpRequestEventMockTrait;
    use InvocationEventInterfaceMockTrait;
    use LoggerMockTrait;
    use PhpFpmProcessMockTrait;

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

    public function testCanHandleWithPublicIndexAndArtisanPresent(): void
    {
        $this->assertTrue((new LaravelHttpEventHandler($this->getLoggerMock(), $this->getPhpFpmProcessMock(), '/tmp'))->canHandle($this->getHttpRequestEventMock()));
    }

    public function testHandleCreatesFastCgiRequestToPublicIndexPhpByDefault(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
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
                    $this->assertSame('/tmp/public/index.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/public/index.php', '/tmp/artisan']);
                    });

        $is_dir->expects($this->any())
               ->willReturn(false);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new LaravelHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleCreatesFastCgiRequestToSpecificPhpFileIfItExists(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
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
                    $this->assertSame('/tmp/public/test.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/public/index.php', '/tmp/artisan', '/tmp/public/test.php']);
                    });

        $is_dir->expects($this->any())
               ->willReturn(false);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new LaravelHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandlePrioritizesPublicOverStorage(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(StaticFileHttpResponse::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->any())
              ->method('getPath')
              ->willReturn('/storage/image.jpg');

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/public/index.php', '/tmp/artisan', '/tmp/public/storage/image.jpg', '/tmp/storage/app/public/image.jpg']);
                    });

        $file_get_contents->expects($this->any())
                          ->willReturn('');

        $is_dir->expects($this->any())
               ->willReturn(false);

        $handler = new LaravelHttpEventHandler($logger, $process, '/tmp');
        $response = $handler->handle($event);

        $this->assertInstanceOf(StaticFileHttpResponse::class, $response);
    }

    public function testHandleResolvesStoragePathIfFileDoesNotExistInPublic(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(StaticFileHttpResponse::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->any())
              ->method('getPath')
              ->willReturn('/storage/image.jpg');

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/public/index.php', '/tmp/artisan', '/tmp/storage/app/public/image.jpg']);
                    });

        $file_get_contents->expects($this->any())
                          ->willReturn('');

        $is_dir->expects($this->any())
               ->willReturn(false);

        $handler = new LaravelHttpEventHandler($logger, $process, '/tmp');
        $response = $handler->handle($event);

        $this->assertInstanceOf(StaticFileHttpResponse::class, $response);
    }

    /**
     * @dataProvider inaccessibleFilesProvider
     */
    public function testHandleReturnsNotFoundHttpResponseForInaccessibleFiles(string $filePath): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->any())
              ->method('getPath')
              ->willReturn($filePath);

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) use ($filePath) {
                        return in_array($path, ['/tmp/public/index.php', '/tmp/artisan', '/tmp/public'.$filePath]);
                    });

        $is_dir->expects($this->any())
               ->willReturn(false);

        $this->assertInstanceOf(NotFoundHttpResponse::class, (new LaravelHttpEventHandler($this->getLoggerMock(), $process, '/tmp'))->handle($event));
    }
}
