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
use Ymir\Runtime\Lambda\Handler\Http\WordPressHttpEventHandler;
use Ymir\Runtime\Lambda\Response\Http\FastCgiHttpResponse;
use Ymir\Runtime\Lambda\Response\Http\NotFoundHttpResponse;
use Ymir\Runtime\Tests\Mock\FunctionMockTrait;
use Ymir\Runtime\Tests\Mock\HttpRequestEventMockTrait;
use Ymir\Runtime\Tests\Mock\InvocationEventInterfaceMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;
use Ymir\Runtime\Tests\Mock\PhpFpmProcessMockTrait;

class WordPressHttpEventHandlerTest extends TestCase
{
    use FunctionMockTrait;
    use HttpRequestEventMockTrait;
    use InvocationEventInterfaceMockTrait;
    use LoggerMockTrait;
    use PhpFpmProcessMockTrait;

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

        $this->assertTrue((new WordPressHttpEventHandler($this->getLoggerMock(), $process, '/tmp'))->canHandle($this->getHttpRequestEventMock()));
    }

    public function testCanHandleWrongEventType(): void
    {
        $process = $this->getPhpFpmProcessMock();

        $this->assertFalse((new WordPressHttpEventHandler($this->getLoggerMock(), $process, ''))->canHandle($this->getInvocationEventInterfaceMock()));
    }

    public function testHandleCreatesFastCgiRequestToFolderIndexPhpIfFileExistsWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(WordPressHttpEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(WordPressHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(WordPressHttpEventHandler::class), 'is_dir');
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
                    $this->assertSame('/tmp/tmp/index.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/index.php', '/tmp/wp-config.php', '/tmp/tmp/index.php', '/tmp/wp-admin/index.php', '/tmp/wp-login.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/wp-config.php'))
                          ->willReturn('');

        $is_dir->expects($this->any())
               ->with($this->identicalTo('/tmp/tmp/'))
               ->willReturn(true);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new WordPressHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleCreatesFastCgiRequestToFolderIndexPhpIfFileExistsWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(WordPressHttpEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(WordPressHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(WordPressHttpEventHandler::class), 'is_dir');
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
                return '/tmp/tmp/index.php' === $request->getScriptFilename();
            }));

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/index.php', '/tmp/wp-config.php', '/tmp/tmp/index.php', '/tmp/wp-admin/index.php', '/tmp/wp-login.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/wp-config.php'))
                          ->willReturn('');

        $is_dir->expects($this->any())
               ->with($this->identicalTo('/tmp/tmp/'))
               ->willReturn(true);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new WordPressHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleCreatesFastCgiRequestToRootIndexPhpByDefaultWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(WordPressHttpEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(WordPressHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(WordPressHttpEventHandler::class), 'is_dir');
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
                    $this->assertSame('/tmp/index.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/index.php', '/tmp/wp-config.php', '/tmp/tmp/index.php', '/tmp/wp-admin/index.php', '/tmp/wp-login.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/wp-config.php'))
                          ->willReturn('');

        $is_dir->expects($this->any())
               ->willReturn(false);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new WordPressHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleCreatesFastCgiRequestToRootIndexPhpByDefaultWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(WordPressHttpEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(WordPressHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(WordPressHttpEventHandler::class), 'is_dir');
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
                    $this->assertSame('/tmp/index.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/index.php', '/tmp/wp-config.php', '/tmp/tmp/index.php', '/tmp/wp-admin/index.php', '/tmp/wp-login.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/wp-config.php'))
                          ->willReturn('');

        $is_dir->expects($this->any())
               ->willReturn(false);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new WordPressHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    /**
     * @dataProvider inaccessibleFilesProvider
     */
    public function testHandleReturnsNotFoundHttpResponseForInaccessibleFiles(string $filePath): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_get_contents = $this->getFunctionMock($this->getNamespace(WordPressHttpEventHandler::class), 'file_get_contents');
        $process = $this->getPhpFpmProcessMock();

        $event->expects($this->exactly(1))
              ->method('getPath')
              ->willReturn($filePath);

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/wp-config.php'))
                          ->willReturn('');

        $this->assertInstanceOf(NotFoundHttpResponse::class, (new WordPressHttpEventHandler($this->getLoggerMock(), $process, '/tmp'))->handle($event));
    }

    public function testHandleRewritesWpAdminUrlWithSubdirectoryMultisiteWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(WordPressHttpEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(WordPressHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(WordPressHttpEventHandler::class), 'is_dir');
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
                    $this->assertSame('/tmp/wp-admin/index.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/index.php', '/tmp/wp-config.php', '/tmp/tmp/index.php', '/tmp/wp-admin/index.php', '/tmp/wp-login.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/wp-config.php'))
                          ->willReturn('define(\'MULTISITE\', true);');

        $is_dir->expects($this->any())
               ->with($this->identicalTo('/tmp/wp-admin/'))
               ->willReturn(true);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new WordPressHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleRewritesWpAdminUrlWithSubdirectoryMultisiteWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(WordPressHttpEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(WordPressHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(WordPressHttpEventHandler::class), 'is_dir');
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
                    $this->assertSame('/tmp/wp-admin/index.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/index.php', '/tmp/wp-config.php', '/tmp/tmp/index.php', '/tmp/wp-admin/index.php', '/tmp/wp-login.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/wp-config.php'))
                          ->willReturn('define(\'MULTISITE\', true);');

        $is_dir->expects($this->any())
               ->with($this->identicalTo('/tmp/wp-admin/'))
               ->willReturn(true);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new WordPressHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleRewritesWpLoginUrlWithMultisiteWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(WordPressHttpEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(WordPressHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(WordPressHttpEventHandler::class), 'is_dir');
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
                    $this->assertSame('/tmp/wp-login.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/index.php', '/tmp/wp-config.php', '/tmp/tmp/index.php', '/tmp/wp-admin/index.php', '/tmp/wp-login.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/wp-config.php'))
                          ->willReturn('define(\'MULTISITE\', true);');

        $is_dir->expects($this->any())
               ->willReturn(false);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new WordPressHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleRewritesWpLoginUrlWithMultisiteWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(WordPressHttpEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(WordPressHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(WordPressHttpEventHandler::class), 'is_dir');
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
                    $this->assertSame('/tmp/wp-login.php', $request->getScriptFilename());

                    return true;
                }));

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/index.php', '/tmp/wp-config.php', '/tmp/tmp/index.php', '/tmp/wp-admin/index.php', '/tmp/wp-login.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/wp-config.php'))
                          ->willReturn('define(\'MULTISITE\', true);');

        $is_dir->expects($this->any())
               ->willReturn(false);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new WordPressHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleWpLoginUrlWithPathInfoWithPayloadVersion1(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(WordPressHttpEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(WordPressHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(WordPressHttpEventHandler::class), 'is_dir');
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
                    $this->assertSame('/tmp/wp-login.php', $request->getScriptFilename());
                    $this->assertSame('/foo', Arr::get($request->getParams(), 'PATH_INFO'));

                    return true;
                }));

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/index.php', '/tmp/wp-config.php', '/tmp/tmp/index.php', '/tmp/wp-admin/index.php', '/tmp/wp-login.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/wp-config.php'))
                          ->willReturn('');

        $is_dir->expects($this->any())
               ->willReturn(false);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new WordPressHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }

    public function testHandleWpLoginUrlWithPathInfoWithPayloadVersion2(): void
    {
        $event = $this->getHttpRequestEventMock();
        $file_exists = $this->getFunctionMock($this->getNamespace(WordPressHttpEventHandler::class), 'file_exists');
        $file_get_contents = $this->getFunctionMock($this->getNamespace(WordPressHttpEventHandler::class), 'file_get_contents');
        $is_dir = $this->getFunctionMock($this->getNamespace(WordPressHttpEventHandler::class), 'is_dir');
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
                    $this->assertSame('/tmp/wp-login.php', $request->getScriptFilename());
                    $this->assertSame('/foo', Arr::get($request->getParams(), 'PATH_INFO'));

                    return true;
                }));

        $file_exists->expects($this->any())
                    ->willReturnCallback(function (string $path) {
                        return in_array($path, ['/tmp/index.php', '/tmp/wp-config.php', '/tmp/tmp/index.php', '/tmp/wp-admin/index.php', '/tmp/wp-login.php']);
                    });

        $file_get_contents->expects($this->any())
                          ->with($this->identicalTo('/tmp/wp-config.php'))
                          ->willReturn('');

        $is_dir->expects($this->any())
               ->willReturn(false);

        $this->assertInstanceOf(FastCgiHttpResponse::class, (new WordPressHttpEventHandler($logger, $process, '/tmp'))->handle($event));
    }
}
