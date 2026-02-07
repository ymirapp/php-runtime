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
use Tightenco\Collect\Support\Arr;
use Ymir\Runtime\FastCgi\FastCgiRequest;
use Ymir\Runtime\Lambda\Handler\Http\RadicleHttpEventHandler;
use Ymir\Runtime\Lambda\Response\Http\FastCgiHttpResponse;
use Ymir\Runtime\Lambda\Response\Http\NotFoundHttpResponse;
use Ymir\Runtime\Lambda\Response\Http\StaticFileHttpResponse;
use Ymir\Runtime\Tests\Mock\FunctionMockTrait;
use Ymir\Runtime\Tests\Mock\HttpRequestEventMockTrait;
use Ymir\Runtime\Tests\Mock\InvocationEventInterfaceMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;
use Ymir\Runtime\Tests\Mock\PhpFpmProcessMockTrait;

class RadicleHttpEventHandlerTest extends TestCase
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
            ['/composer/installed.json'],
            ['/wp-cli.local.yml'],
            ['/wp-cli.yml'],
        ];
    }

    public function testCanHandleWithApplicationAndWpConfigPresent(): void
    {
        $process = $this->getPhpFpmProcessMock();

        $this->assertTrue((new RadicleHttpEventHandler($this->getLoggerMock(), $process, '/tmp'))->canHandle($this->getHttpRequestEventMock()));
    }

    public function testCanHandleWrongEventType(): void
    {
        $process = $this->getPhpFpmProcessMock();

        $this->assertFalse((new RadicleHttpEventHandler($this->getLoggerMock(), $process, ''))->canHandle($this->getInvocationEventInterfaceMock()));
    }

    public function testHandleCreatesFastCgiRequestToFolderIndexPhpIfFileExistsWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $namespace = $this->getNamespace(RadicleHttpEventHandler::class);
        $file_exists = $this->getFunctionMock($namespace, 'file_exists');
        $file_get_contents = $this->getFunctionMock($namespace, 'file_get_contents');
        $is_dir = $this->getFunctionMock($namespace, 'is_dir');
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
                    $this->assertSame('/tmp/public/tmp/index.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())->willReturn(true);
        $file_get_contents->expects($this->any())->willReturn('');
        $is_dir->expects($this->any())->willReturnCallback(function (string $path) {
            return in_array($path, ['/tmp/public/tmp', '/tmp/public/tmp/']);
        });

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleCreatesFastCgiRequestToFolderIndexPhpIfFileExistsWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $namespace = $this->getNamespace(RadicleHttpEventHandler::class);
        $file_exists = $this->getFunctionMock($namespace, 'file_exists');
        $file_get_contents = $this->getFunctionMock($namespace, 'file_get_contents');
        $is_dir = $this->getFunctionMock($namespace, 'is_dir');
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
                    $this->assertSame('/tmp/public/tmp/index.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())->willReturn(true);
        $file_get_contents->expects($this->any())->willReturn('');
        $is_dir->expects($this->any())->willReturnCallback(function (string $path) {
            return in_array($path, ['/tmp/public/tmp', '/tmp/public/tmp/']);
        });

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleCreatesFastCgiRequestToPublicDirectoryWithContentPathsWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $namespace = $this->getNamespace(RadicleHttpEventHandler::class);
        $file_exists = $this->getFunctionMock($namespace, 'file_exists');
        $file_get_contents = $this->getFunctionMock($namespace, 'file_get_contents');
        $is_dir = $this->getFunctionMock($namespace, 'is_dir');
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
                    $this->assertSame('/tmp/public/content/plugins/file.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())->willReturn(true);
        $file_get_contents->expects($this->any())->willReturn('');
        $is_dir->expects($this->any())->willReturn(false);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleCreatesFastCgiRequestToPublicDirectoryWithContentPathsWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $namespace = $this->getNamespace(RadicleHttpEventHandler::class);
        $file_exists = $this->getFunctionMock($namespace, 'file_exists');
        $file_get_contents = $this->getFunctionMock($namespace, 'file_get_contents');
        $is_dir = $this->getFunctionMock($namespace, 'is_dir');
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
                    $this->assertSame('/tmp/public/content/plugins/file.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())->willReturn(true);
        $file_get_contents->expects($this->any())->willReturn('');
        $is_dir->expects($this->any())->willReturn(false);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleCreatesFastCgiRequestToRootIndexPhpByDefaultWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $namespace = $this->getNamespace(RadicleHttpEventHandler::class);
        $file_exists = $this->getFunctionMock($namespace, 'file_exists');
        $file_get_contents = $this->getFunctionMock($namespace, 'file_get_contents');
        $is_dir = $this->getFunctionMock($namespace, 'is_dir');
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
                    $this->assertSame('/tmp/public/index.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())->willReturnCallback(function (string $path) {
            return str_ends_with($path, '/public/index.php');
        });
        $file_get_contents->expects($this->any())->willReturn('');
        $is_dir->expects($this->any())->willReturn(false);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleCreatesFastCgiRequestToRootIndexPhpByDefaultWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $namespace = $this->getNamespace(RadicleHttpEventHandler::class);
        $file_exists = $this->getFunctionMock($namespace, 'file_exists');
        $file_get_contents = $this->getFunctionMock($namespace, 'file_get_contents');
        $is_dir = $this->getFunctionMock($namespace, 'is_dir');
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
                    $this->assertSame('/tmp/public/index.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())->willReturnCallback(function (string $path) {
            return str_ends_with($path, '/public/index.php');
        });
        $file_get_contents->expects($this->any())->willReturn('');
        $is_dir->expects($this->any())->willReturn(false);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleCreatesFastCgiRequestToWebDirectoryWithWpPathsWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $namespace = $this->getNamespace(RadicleHttpEventHandler::class);
        $file_exists = $this->getFunctionMock($namespace, 'file_exists');
        $file_get_contents = $this->getFunctionMock($namespace, 'file_get_contents');
        $is_dir = $this->getFunctionMock($namespace, 'is_dir');
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
                    $this->assertSame('/tmp/public/wp/tmp/index.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())->willReturn(true);
        $file_get_contents->expects($this->any())->willReturn('');
        $is_dir->expects($this->any())->willReturnCallback(function (string $path) {
            return in_array($path, ['/tmp/public/wp/tmp', '/tmp/public/wp/tmp/']);
        });

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleCreatesFastCgiRequestToWebDirectoryWithWpPathsWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $namespace = $this->getNamespace(RadicleHttpEventHandler::class);
        $file_exists = $this->getFunctionMock($namespace, 'file_exists');
        $file_get_contents = $this->getFunctionMock($namespace, 'file_get_contents');
        $is_dir = $this->getFunctionMock($namespace, 'is_dir');
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
                    $this->assertSame('/tmp/public/wp/tmp/index.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())->willReturn(true);
        $file_get_contents->expects($this->any())->willReturn('');
        $is_dir->expects($this->any())->willReturnCallback(function (string $path) {
            return in_array($path, ['/tmp/public/wp/tmp', '/tmp/public/wp/tmp/']);
        });

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleDoesntReturnStaticFileHttpResponseForFileOutsideWebDirectoryWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $namespace = $this->getNamespace(RadicleHttpEventHandler::class);
        $file_exists = $this->getFunctionMock($namespace, 'file_exists');
        $file_get_contents = $this->getFunctionMock($namespace, 'file_get_contents');
        $file_get_contents_static = $this->getFunctionMock('Ymir\Runtime\Lambda\Response\Http', 'file_get_contents');
        $is_dir = $this->getFunctionMock($namespace, 'is_dir');
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

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, [getcwd().'/bedrock/application.php', getcwd().'/foo']);
                    });
        $file_get_contents->expects($this->any())->willReturn('');
        $file_get_contents_static->expects($this->any())->willReturn('');
        $is_dir->expects($this->any())->willReturn(false);

        $response = (new RadicleHttpEventHandler($logger, $process, getcwd()))->handle($event);

        $this->assertInstanceOf(FastCgiHttpResponse::class, $response);
        $this->assertFalse($response->isCompressible());
    }

    public function testHandleDoesntReturnStaticFileHttpResponseForFileOutsideWebDirectoryWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $namespace = $this->getNamespace(RadicleHttpEventHandler::class);
        $file_exists = $this->getFunctionMock($namespace, 'file_exists');
        $file_get_contents = $this->getFunctionMock($namespace, 'file_get_contents');
        $file_get_contents_static = $this->getFunctionMock('Ymir\Runtime\Lambda\Response\Http', 'file_get_contents');
        $is_dir = $this->getFunctionMock($namespace, 'is_dir');
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

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, [getcwd().'/bedrock/application.php', getcwd().'/foo']);
                    });
        $file_get_contents->expects($this->any())->willReturn('');
        $file_get_contents_static->expects($this->any())->willReturn('');
        $is_dir->expects($this->any())->willReturn(false);

        $response = (new RadicleHttpEventHandler($logger, $process, getcwd()))->handle($event);

        $this->assertInstanceOf(FastCgiHttpResponse::class, $response);
        $this->assertFalse($response->isCompressible());
    }

    public function testHandleDoesntReturnStaticFileHttpResponseForPhpFileWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $namespace = $this->getNamespace(RadicleHttpEventHandler::class);
        $file_exists = $this->getFunctionMock($namespace, 'file_exists');
        $file_get_contents = $this->getFunctionMock($namespace, 'file_get_contents');
        $is_dir = $this->getFunctionMock($namespace, 'is_dir');
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

        $file_exists->expects($this->any())->willReturn(true);
        $file_get_contents->expects($this->any())->willReturn('');
        $is_dir->expects($this->any())->willReturn(false);

        $response = (new RadicleHttpEventHandler($logger, $process, '/tmp'))->handle($event);

        $this->assertInstanceOf(FastCgiHttpResponse::class, $response);
        $this->assertFalse($response->isCompressible());
    }

    public function testHandleDoesntReturnStaticFileHttpResponseForPhpFileWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $namespace = $this->getNamespace(RadicleHttpEventHandler::class);
        $file_exists = $this->getFunctionMock($namespace, 'file_exists');
        $file_get_contents = $this->getFunctionMock($namespace, 'file_get_contents');
        $is_dir = $this->getFunctionMock($namespace, 'is_dir');
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

        $file_exists->expects($this->any())->willReturn(true);
        $file_get_contents->expects($this->any())->willReturn('');
        $is_dir->expects($this->any())->willReturn(false);

        $response = (new RadicleHttpEventHandler($logger, $process, '/tmp'))->handle($event);

        $this->assertInstanceOf(FastCgiHttpResponse::class, $response);
        $this->assertFalse($response->isCompressible());
    }

    public function testHandleDoesntRewriteWpLoginUrlWithoutWpPrefixAndWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $namespace = $this->getNamespace(RadicleHttpEventHandler::class);
        $file_exists = $this->getFunctionMock($namespace, 'file_exists');
        $file_get_contents = $this->getFunctionMock($namespace, 'file_get_contents');
        $is_dir = $this->getFunctionMock($namespace, 'is_dir');
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
                    $this->assertSame('/tmp/public/index.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())->willReturnCallback(function (string $path) {
            return in_array($path, ['/tmp/public/index.php']);
        });
        $file_get_contents->expects($this->any())->willReturn('');
        $is_dir->expects($this->any())->willReturn(false);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleDoesntRewriteWpLoginUrlWithoutWpPrefixAndWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $namespace = $this->getNamespace(RadicleHttpEventHandler::class);
        $file_exists = $this->getFunctionMock($namespace, 'file_exists');
        $file_get_contents = $this->getFunctionMock($namespace, 'file_get_contents');
        $is_dir = $this->getFunctionMock($namespace, 'is_dir');
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
                    $this->assertSame('/tmp/public/index.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())->willReturnCallback(function (string $path) {
            return in_array($path, ['/tmp/public/index.php']);
        });
        $file_get_contents->expects($this->any())->willReturn('');
        $is_dir->expects($this->any())->willReturn(false);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    /**
     * @dataProvider inaccessibleFilesProvider
     */
    public function testHandleReturnsNotFoundHttpResponseForInaccessibleFiles(string $filePath): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_get_contents = $this->getFunctionMock($this->getNamespace(RadicleHttpEventHandler::class), 'file_get_contents');
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->exactly(1))
              ->method('getPath')
              ->willReturn($filePath);

        $file_get_contents->expects($this->any())->willReturn('');

        $this->assertInstanceOf(NotFoundHttpResponse::class, (new RadicleHttpEventHandler($this->getLoggerMock(), $process, '/tmp'))->handle($event));
    }

    public function testHandleReturnsStaticFileHttpResponseForFileInsideWebDirectory(): void
    {
        $event = $this->getHttpRequestEventMock();
        $namespace = $this->getNamespace(RadicleHttpEventHandler::class);
        $file_exists = $this->getFunctionMock($namespace, 'file_exists');
        $file_get_contents = $this->getFunctionMock($namespace, 'file_get_contents');
        $file_get_contents_static = $this->getFunctionMock('Ymir\Runtime\Lambda\Response\Http', 'file_get_contents');
        $is_dir = $this->getFunctionMock($namespace, 'is_dir');
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->once())
              ->method('getPath')
              ->willReturn('/foo');

        $file_exists->expects($this->any())->willReturnCallback(function (string $path) {
            return str_ends_with($path, '/public/foo');
        });
        $file_get_contents->expects($this->any())->willReturn('');
        $file_get_contents_static->expects($this->any())->willReturn('');
        $is_dir->expects($this->any())->willReturn(false);

        $this->assertInstanceOf(StaticFileHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleRewritesWpAdminUrlWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $namespace = $this->getNamespace(RadicleHttpEventHandler::class);
        $file_exists = $this->getFunctionMock($namespace, 'file_exists');
        $file_get_contents = $this->getFunctionMock($namespace, 'file_get_contents');
        $is_dir = $this->getFunctionMock($namespace, 'is_dir');
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
                    $this->assertSame('/tmp/public/wp/wp-admin/index.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())->willReturn(true);
        $file_get_contents->expects($this->any())->willReturn('');
        $is_dir->expects($this->any())->willReturnCallback(function (string $path) {
            return in_array($path, ['/tmp/public/wp/wp-admin', '/tmp/public/wp/wp-admin/']);
        });

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleRewritesWpAdminUrlWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $namespace = $this->getNamespace(RadicleHttpEventHandler::class);
        $file_exists = $this->getFunctionMock($namespace, 'file_exists');
        $file_get_contents = $this->getFunctionMock($namespace, 'file_get_contents');
        $is_dir = $this->getFunctionMock($namespace, 'is_dir');
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
                    $this->assertSame('/tmp/public/wp/wp-admin/index.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())->willReturn(true);
        $file_get_contents->expects($this->any())->willReturn('');
        $is_dir->expects($this->any())->willReturnCallback(function (string $path) {
            return in_array($path, ['/tmp/public/wp/wp-admin', '/tmp/public/wp/wp-admin/']);
        });

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleRewritesWpAdminUrlWithSubdirectoryMultisiteWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $namespace = $this->getNamespace(RadicleHttpEventHandler::class);
        $file_exists = $this->getFunctionMock($namespace, 'file_exists');
        $file_get_contents = $this->getFunctionMock($namespace, 'file_get_contents');
        $is_dir = $this->getFunctionMock($namespace, 'is_dir');
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
                    $this->assertSame('/tmp/public/wp/wp-admin/index.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())->willReturn(true);
        $file_get_contents->expects($this->any())->willReturn('Config::define(\'MULTISITE\', true);');
        $is_dir->expects($this->any())->willReturnCallback(function (string $path) {
            return in_array($path, ['/tmp/public/wp/wp-admin', '/tmp/public/wp/wp-admin/']);
        });

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleRewritesWpAdminUrlWithSubdirectoryMultisiteWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $namespace = $this->getNamespace(RadicleHttpEventHandler::class);
        $file_exists = $this->getFunctionMock($namespace, 'file_exists');
        $file_get_contents = $this->getFunctionMock($namespace, 'file_get_contents');
        $is_dir = $this->getFunctionMock($namespace, 'is_dir');
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
                    $this->assertSame('/tmp/public/wp/wp-admin/index.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())->willReturn(true);
        $file_get_contents->expects($this->any())->willReturn('Config::define(\'MULTISITE\', true);');
        $is_dir->expects($this->any())->willReturnCallback(function (string $path) {
            return in_array($path, ['/tmp/public/wp/wp-admin', '/tmp/public/wp/wp-admin/']);
        });

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleRewritesWpLoginUrlWithSubdirectoryMultisiteWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $namespace = $this->getNamespace(RadicleHttpEventHandler::class);
        $file_exists = $this->getFunctionMock($namespace, 'file_exists');
        $file_get_contents = $this->getFunctionMock($namespace, 'file_get_contents');
        $is_dir = $this->getFunctionMock($namespace, 'is_dir');
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
                    $this->assertSame('/tmp/public/wp/wp-login.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())->willReturn(true);
        $file_get_contents->expects($this->any())->willReturn('Config::define(\'MULTISITE\', true);');
        $is_dir->expects($this->any())->willReturn(false);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleRewritesWpLoginUrlWithSubdirectoryMultisiteWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $namespace = $this->getNamespace(RadicleHttpEventHandler::class);
        $file_exists = $this->getFunctionMock($namespace, 'file_exists');
        $file_get_contents = $this->getFunctionMock($namespace, 'file_get_contents');
        $is_dir = $this->getFunctionMock($namespace, 'is_dir');
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
                    $this->assertSame('/tmp/public/wp/wp-login.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())->willReturn(true);
        $file_get_contents->expects($this->any())->willReturn('Config::define(\'MULTISITE\', true);');
        $is_dir->expects($this->any())->willReturn(false);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleWpLoginUrlWithPathInfoWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $namespace = $this->getNamespace(RadicleHttpEventHandler::class);
        $file_exists = $this->getFunctionMock($namespace, 'file_exists');
        $file_get_contents = $this->getFunctionMock($namespace, 'file_get_contents');
        $is_dir = $this->getFunctionMock($namespace, 'is_dir');
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
                    $this->assertSame('/tmp/public/wp/wp-login.php', $request->getScriptFilename());
                    $this->assertSame('/foo', Arr::get($request->getParams(), 'PATH_INFO'));

                    return true;
                }));

        $file_exists->expects($this->any())->willReturn(true);
        $file_get_contents->expects($this->any())->willReturn('');
        $is_dir->expects($this->any())->willReturn(false);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleWpLoginUrlWithPathInfoWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $namespace = $this->getNamespace(RadicleHttpEventHandler::class);
        $file_exists = $this->getFunctionMock($namespace, 'file_exists');
        $file_get_contents = $this->getFunctionMock($namespace, 'file_get_contents');
        $is_dir = $this->getFunctionMock($namespace, 'is_dir');
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
                    $this->assertSame('/tmp/public/wp-login.php', $request->getScriptFilename());
                    $this->assertSame('/foo', Arr::get($request->getParams(), 'PATH_INFO'));

                    return true;
                }));

        $file_exists->expects($this->any())->willReturn(true);
        $file_get_contents->expects($this->any())->willReturn('');
        $is_dir->expects($this->any())->willReturn(false);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleWpLoginUrlWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $namespace = $this->getNamespace(RadicleHttpEventHandler::class);
        $file_exists = $this->getFunctionMock($namespace, 'file_exists');
        $file_get_contents = $this->getFunctionMock($namespace, 'file_get_contents');
        $is_dir = $this->getFunctionMock($namespace, 'is_dir');
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
                    $this->assertSame('/tmp/public/wp-login.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())->willReturn(true);
        $file_get_contents->expects($this->any())->willReturn('');
        $is_dir->expects($this->any())->willReturn(false);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleWpLoginUrlWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $namespace = $this->getNamespace(RadicleHttpEventHandler::class);
        $file_exists = $this->getFunctionMock($namespace, 'file_exists');
        $file_get_contents = $this->getFunctionMock($namespace, 'file_get_contents');
        $is_dir = $this->getFunctionMock($namespace, 'is_dir');
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
                    $this->assertSame('/tmp/public/wp-login.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())->willReturn(true);
        $file_get_contents->expects($this->any())->willReturn('');
        $is_dir->expects($this->any())->willReturn(false);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new RadicleHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }
}
