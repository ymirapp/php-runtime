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
use Ymir\Runtime\Lambda\Handler\Http\AbstractHttpRequestEventHandler;
use Ymir\Runtime\Lambda\Handler\Http\BedrockHttpEventHandler;
use Ymir\Runtime\Lambda\Response\Http\FastCgiHttpResponse;
use Ymir\Runtime\Lambda\Response\Http\NotFoundHttpResponse;
use Ymir\Runtime\Lambda\Response\Http\StaticFileHttpResponse;
use Ymir\Runtime\Tests\Mock\FunctionMockTrait;
use Ymir\Runtime\Tests\Mock\HttpRequestEventMockTrait;
use Ymir\Runtime\Tests\Mock\InvocationEventInterfaceMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;
use Ymir\Runtime\Tests\Mock\PhpFpmProcessMockTrait;

class BedrockHttpEventHandlerTest extends TestCase
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

        $this->assertTrue((new BedrockHttpEventHandler($this->getLoggerMock(), $process, '/tmp'))->canHandle($this->getHttpRequestEventMock()));
    }

    public function testCanHandleWrongEventType(): void
    {
        $process = $this->getPhpFpmProcessMock();

        $this->assertFalse((new BedrockHttpEventHandler($this->getLoggerMock(), $process, ''))->canHandle($this->getInvocationEventInterfaceMock()));
    }

    public function testHandleCreatesFastCgiRequestToFolderIndexPhpIfFileExistsWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(BedrockHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
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
                    $this->assertSame('/tmp/web/tmp/index.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/config/application.php', '/tmp/web/app/mu-plugins/bedrock-autoloader.php', '/tmp/web/tmp/index.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/config/application.php'))
                          ->willReturn('');

        $is_dir->expects($this->any())
               ->willReturnCallback(function (string $path) {
                   return '/tmp/web/tmp/' === $path;
               });

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleCreatesFastCgiRequestToFolderIndexPhpIfFileExistsWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(BedrockHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
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
                    $this->assertSame('/tmp/web/tmp/index.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/config/application.php', '/tmp/web/app/mu-plugins/bedrock-autoloader.php', '/tmp/web/tmp/index.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/config/application.php'))
                          ->willReturn('');

        $is_dir->expects($this->any())
               ->willReturnCallback(function (string $path) {
                   return '/tmp/web/tmp/' === $path;
               });

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleCreatesFastCgiRequestToRootIndexPhpByDefaultWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(BedrockHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
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
                    $this->assertSame('/tmp/web/index.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/config/application.php', '/tmp/web/app/mu-plugins/bedrock-autoloader.php', '/tmp/web/index.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/config/application.php'))
                          ->willReturn('');

        $is_dir->expects($this->any())
               ->willReturn(false);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleCreatesFastCgiRequestToRootIndexPhpByDefaultWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(BedrockHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
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
                    $this->assertSame('/tmp/web/index.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/config/application.php', '/tmp/web/app/mu-plugins/bedrock-autoloader.php', '/tmp/web/index.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/config/application.php'))
                          ->willReturn('');

        $is_dir->expects($this->any())
               ->willReturn(false);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleCreatesFastCgiRequestToWebDirectoryWithAppPathsWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(BedrockHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->exactly(3))
              ->method('getPath')
              ->willReturn('app/plugins/file.php');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('1.0');

        $logger->expects($this->once())
               ->method('debug');

        $process->expects($this->once())
                ->method('handle')
                ->with($this->callback(function (FastCgiRequest $request) {
                    $this->assertSame('/tmp/web/app/plugins/file.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/config/application.php', '/tmp/web/app/mu-plugins/bedrock-autoloader.php', '/tmp/web/app/plugins/file.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/config/application.php'))
                          ->willReturn('');

        $is_dir->expects($this->any())
               ->willReturn(false);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleCreatesFastCgiRequestToWebDirectoryWithAppPathsWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(BedrockHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->exactly(3))
              ->method('getPath')
              ->willReturn('app/plugins/file.php');

        $event->expects($this->once())
              ->method('getPayloadVersion')
              ->willReturn('2.0');

        $logger->expects($this->once())
               ->method('debug');

        $process->expects($this->once())
                ->method('handle')
                ->with($this->callback(function (FastCgiRequest $request) {
                    $this->assertSame('/tmp/web/app/plugins/file.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/config/application.php', '/tmp/web/app/mu-plugins/bedrock-autoloader.php', '/tmp/web/app/plugins/file.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/config/application.php'))
                          ->willReturn('');

        $is_dir->expects($this->any())
               ->willReturn(false);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleCreatesFastCgiRequestToWebDirectoryWithWpPathsWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(BedrockHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
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
                    $this->assertSame('/tmp/web/wp/tmp/index.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/config/application.php', '/tmp/web/app/mu-plugins/bedrock-autoloader.php', '/tmp/web/wp/tmp/index.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/config/application.php'))
                          ->willReturn('');

        $is_dir->expects($this->any())
               ->willReturnCallback(function (string $path) {
                   return in_array($path, ['/tmp/web/wp/tmp', '/tmp/web/wp/tmp/']);
               });

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleCreatesFastCgiRequestToWebDirectoryWithWpPathsWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(BedrockHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
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
                    $this->assertSame('/tmp/web/wp/tmp/index.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/config/application.php', '/tmp/web/app/mu-plugins/bedrock-autoloader.php', '/tmp/web/wp/tmp/index.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/config/application.php'))
                          ->willReturn('');

        $is_dir->expects($this->any())
               ->willReturnCallback(function (string $path) {
                   return in_array($path, ['/tmp/web/wp/tmp', '/tmp/web/wp/tmp/']);
               });

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleDoesntReturnStaticFileHttpResponseForFileOutsideWebDirectoryWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(BedrockHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
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
                        return in_array($path, ['/tmp/config/application.php', '/tmp/web/app/mu-plugins/bedrock-autoloader.php', '/tmp/web/wp/wp-login.php', '/tmp/foo', '/tmp/web/index.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/config/application.php'))
                          ->willReturn('');

        $is_dir->expects($this->any())
               ->willReturn(false);

        $response = (new BedrockHttpEventHandler($logger, $process, '/tmp'))->handle($event);

        $this->assertInstanceOf(FastCgiHttpResponse::class, $response);
        $this->assertFalse($response->isCompressible());
    }

    public function testHandleDoesntReturnStaticFileHttpResponseForFileOutsideWebDirectoryWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(BedrockHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
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
                        return in_array($path, ['/tmp/config/application.php', '/tmp/web/app/mu-plugins/bedrock-autoloader.php', '/tmp/web/wp/wp-login.php', '/tmp/foo', '/tmp/web/index.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/config/application.php'))
                          ->willReturn('');

        $is_dir->expects($this->any())
               ->willReturn(false);

        $response = (new BedrockHttpEventHandler($logger, $process, '/tmp'))->handle($event);

        $this->assertInstanceOf(FastCgiHttpResponse::class, $response);
        $this->assertFalse($response->isCompressible());
    }

    public function testHandleDoesntReturnStaticFileHttpResponseForPhpFileWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(BedrockHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
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

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/config/application.php', '/tmp/web/app/mu-plugins/bedrock-autoloader.php', '/tmp/web/wp/wp-login.php', '/tmp/web/foo.php', '/tmp/web/index.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/config/application.php'))
                          ->willReturn('');

        $is_dir->expects($this->any())
               ->willReturn(false);

        $response = (new BedrockHttpEventHandler($logger, $process, '/tmp'))->handle($event);

        $this->assertInstanceOf(FastCgiHttpResponse::class, $response);
        $this->assertFalse($response->isCompressible());
    }

    public function testHandleDoesntReturnStaticFileHttpResponseForPhpFileWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(BedrockHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
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

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/config/application.php', '/tmp/web/app/mu-plugins/bedrock-autoloader.php', '/tmp/web/wp/wp-login.php', '/tmp/web/foo.php', '/tmp/web/index.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/config/application.php'))
                          ->willReturn('');

        $is_dir->expects($this->any())
               ->willReturn(false);

        $response = (new BedrockHttpEventHandler($logger, $process, '/tmp'))->handle($event);

        $this->assertInstanceOf(FastCgiHttpResponse::class, $response);
        $this->assertFalse($response->isCompressible());
    }

    public function testHandleDoesntRewriteWpLoginUrlWithoutWpPrefixAndWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(BedrockHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
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
                    $this->assertSame('/tmp/web/index.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/config/application.php', '/tmp/web/app/mu-plugins/bedrock-autoloader.php', '/tmp/web/wp/wp-login.php', '/tmp/web/index.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/config/application.php'))
                          ->willReturn('');

        $is_dir->expects($this->any())
               ->willReturn(false);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleDoesntRewriteWpLoginUrlWithoutWpPrefixAndWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(BedrockHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
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
                    $this->assertSame('/tmp/web/index.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/config/application.php', '/tmp/web/app/mu-plugins/bedrock-autoloader.php', '/tmp/web/wp/wp-login.php', '/tmp/web/index.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/config/application.php'))
                          ->willReturn('');

        $is_dir->expects($this->any())
               ->willReturn(false);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    /**
     * @dataProvider inaccessibleFilesProvider
     */
    public function testHandleReturnsNotFoundHttpResponseForInaccessibleFiles(string $filePath): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(BedrockHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->exactly(1))
              ->method('getPath')
              ->willReturn($filePath);

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) use ($filePath) {
                        return in_array($path, ['/tmp/config/application.php', '/tmp/web/app/mu-plugins/bedrock-autoloader.php', '/tmp'.$filePath]);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/config/application.php'))
                          ->willReturn('');

        $is_dir->expects($this->any())
               ->willReturn(false);

        $this->assertInstanceOf(NotFoundHttpResponse::class, (new BedrockHttpEventHandler($this->getLoggerMock(), $process, '/tmp'))->handle($event));
    }

    public function testHandleReturnsStaticFileHttpResponseForFileInsideWebDirectory(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(StaticFileHttpResponse::class), 'file_get_contents');
        $file_get_contents_bedrock = $this->getFunctionMock($this->getNamespace(BedrockHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
        $logger = $this->getLoggerMock();
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->once())
              ->method('getPath')
              ->willReturn('/foo');

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/config/application.php', '/tmp/web/app/mu-plugins/bedrock-autoloader.php', '/tmp/web/wp/wp-login.php', '/tmp/web/foo']);
                    });

        $file_get_contents_bedrock->expects($this->any())
                          ->with($this->identicalTo('/tmp/config/application.php'))
                          ->willReturn('');

        $file_get_contents->expects($this->any())
                          ->willReturn('');

        $is_dir->expects($this->any())
               ->willReturn(false);

        $this->assertInstanceOf(StaticFileHttpResponse::class, (new BedrockHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleRewritesWpAdminUrlWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(BedrockHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
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
                    $this->assertSame('/tmp/web/wp/wp-admin/index.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/config/application.php', '/tmp/web/app/mu-plugins/bedrock-autoloader.php', '/tmp/web/wp/wp-admin/index.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/config/application.php'))
                          ->willReturn('');

        $is_dir->expects($this->any())
               ->willReturnCallback(function (string $path) {
                   return '/tmp/web/wp/wp-admin/' === $path;
               });

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleRewritesWpAdminUrlWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(BedrockHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
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
                    $this->assertSame('/tmp/web/wp/wp-admin/index.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/config/application.php', '/tmp/web/app/mu-plugins/bedrock-autoloader.php', '/tmp/web/wp/wp-admin/index.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/config/application.php'))
                          ->willReturn('');

        $is_dir->expects($this->any())
               ->willReturnCallback(function (string $path) {
                   return '/tmp/web/wp/wp-admin/' === $path;
               });

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleRewritesWpAdminUrlWithSubdirectoryMultisiteWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(BedrockHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
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
                    $this->assertSame('/tmp/web/wp/wp-admin/index.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/config/application.php', '/tmp/web/app/mu-plugins/bedrock-autoloader.php', '/tmp/web/wp/wp-admin/index.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/config/application.php'))
                          ->willReturn('Config::define(\'MULTISITE\', true);');

        $is_dir->expects($this->any())
               ->willReturnCallback(function (string $path) {
                   return '/tmp/web/wp/wp-admin/' === $path;
               });

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleRewritesWpAdminUrlWithSubdirectoryMultisiteWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(BedrockHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
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
                    $this->assertSame('/tmp/web/wp/wp-admin/index.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/config/application.php', '/tmp/web/app/mu-plugins/bedrock-autoloader.php', '/tmp/web/wp/wp-admin/index.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/config/application.php'))
                          ->willReturn('Config::define(\'MULTISITE\', true);');

        $is_dir->expects($this->any())
               ->willReturnCallback(function (string $path) {
                   return '/tmp/web/wp/wp-admin/' === $path;
               });

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleRewritesWpLoginUrlWithSubdirectoryMultisiteWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(BedrockHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
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
                    $this->assertSame('/tmp/web/wp/wp-login.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/config/application.php', '/tmp/web/app/mu-plugins/bedrock-autoloader.php', '/tmp/web/wp/wp-login.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/config/application.php'))
                          ->willReturn('Config::define(\'MULTISITE\', true);');

        $is_dir->expects($this->any())
               ->willReturn(false);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleRewritesWpLoginUrlWithSubdirectoryMultisiteWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(BedrockHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
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
                    $this->assertSame('/tmp/web/wp/wp-login.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/config/application.php', '/tmp/web/app/mu-plugins/bedrock-autoloader.php', '/tmp/web/wp/wp-login.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/config/application.php'))
                          ->willReturn('Config::define(\'MULTISITE\', true);');

        $is_dir->expects($this->any())
               ->willReturn(false);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleWpLoginUrlWithPathInfoWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(BedrockHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
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
                    $this->assertSame('/tmp/web/wp/wp-login.php', $request->getScriptFilename());
                    $this->assertSame('/foo', Arr::get($request->getParams(), 'PATH_INFO'));

                    return true;
                }));

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/config/application.php', '/tmp/web/app/mu-plugins/bedrock-autoloader.php', '/tmp/web/wp/wp-login.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/config/application.php'))
                          ->willReturn('');

        $is_dir->expects($this->any())
               ->willReturn(false);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleWpLoginUrlWithPathInfoWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(BedrockHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
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
                    $this->assertSame('/tmp/web/wp/wp-login.php', $request->getScriptFilename());
                    $this->assertSame('/foo', Arr::get($request->getParams(), 'PATH_INFO'));

                    return true;
                }));

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/config/application.php', '/tmp/web/app/mu-plugins/bedrock-autoloader.php', '/tmp/web/wp/wp-login.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/config/application.php'))
                          ->willReturn('');

        $is_dir->expects($this->any())
               ->willReturn(false);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleWpLoginUrlWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(BedrockHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
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
                    $this->assertSame('/tmp/web/wp/wp-login.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/config/application.php', '/tmp/web/app/mu-plugins/bedrock-autoloader.php', '/tmp/web/wp/wp-login.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/config/application.php'))
                          ->willReturn('');

        $is_dir->expects($this->any())
               ->willReturn(false);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleWpLoginUrlWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(BedrockHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(AbstractHttpRequestEventHandler::class), 'is_dir');
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
                    $this->assertSame('/tmp/web/wp/wp-login.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/config/application.php', '/tmp/web/app/mu-plugins/bedrock-autoloader.php', '/tmp/web/wp/wp-login.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/config/application.php'))
                          ->willReturn('');

        $is_dir->expects($this->any())
               ->willReturn(false);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new BedrockHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }
}
