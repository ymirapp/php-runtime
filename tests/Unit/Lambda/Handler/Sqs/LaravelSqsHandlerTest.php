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

namespace Ymir\Runtime\Tests\Unit\Lambda\Handler\Sqs;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Ymir\Runtime\Exception\InvalidHandlerEventException;
use Ymir\Runtime\Lambda\Handler\Sqs\LaravelSqsHandler;
use Ymir\Runtime\Lambda\InvocationEvent\SqsEvent;
use Ymir\Runtime\Tests\Mock\InvocationContextMockTrait;
use Ymir\Runtime\Tests\Mock\InvocationEventInterfaceMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;

class LaravelSqsHandlerTest extends TestCase
{
    use InvocationContextMockTrait;
    use InvocationEventInterfaceMockTrait;
    use LoggerMockTrait;

    private $filesystem;
    private $tempDir;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->tempDir = sys_get_temp_dir().'/ymir_test_'.uniqid();
        $this->filesystem->mkdir($this->tempDir.'/public');
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempDir);
    }

    public function testCanHandleReturnsFalseForWrongEvent(): void
    {
        $handler = new LaravelSqsHandler($this->getLoggerMock(), '/tmp');

        $this->assertFalse($handler->canHandle($this->getInvocationEventInterfaceMock()));
    }

    public function testCanHandleReturnsTrueIfArtisanAndPublicIndexExist(): void
    {
        $handler = new LaravelSqsHandler($this->getLoggerMock(), '/tmp');

        $this->assertTrue($handler->canHandle(new SqsEvent($this->getInvocationContextMock())));
    }

    public function testHandleCollectsFailuresIfProcessFails(): void
    {
        $context = $this->getInvocationContextMock();
        $context->method('getRemainingTimeInMs')->willReturn(10000);

        $event = new SqsEvent($context, [
            'Records' => [
                ['messageId' => 'id1', 'body' => '{"foo":"bar"}'],
            ],
        ]);

        $logger = $this->getLoggerMock();
        $logger->expects($this->once())
               ->method('error')
               ->with($this->stringContains('Processing SQS message [id1] failed: Laravel queue job failed'));

        $handler = new LaravelSqsHandler($logger, '/tmp');
        $response = $handler->handle($event);

        $this->assertSame([
            'batchItemFailures' => [
                ['itemIdentifier' => 'id1'],
            ],
        ], $response->getResponseData());
    }

    public function testHandleCollectsFailuresOnJsonError(): void
    {
        $event = new SqsEvent($this->getInvocationContextMock(), [
            'Records' => [
                ['messageId' => 'id1', 'body' => "\xB1\x31"],
            ],
        ]);

        $logger = $this->getLoggerMock();
        $logger->expects($this->once())
               ->method('error')
               ->with($this->stringContains('Processing SQS message [id1] failed: Failed to encode SQS message [id1]'));

        $handler = new LaravelSqsHandler($logger, '/tmp');
        $response = $handler->handle($event);

        $this->assertSame([
            'batchItemFailures' => [
                ['itemIdentifier' => 'id1'],
            ],
        ], $response->getResponseData());
    }

    public function testHandleThrowsExceptionForWrongEvent(): void
    {
        $this->expectException(InvalidHandlerEventException::class);
        $this->expectExceptionMessageMatches('/LaravelSqsHandler cannot handle Mock_InvocationEventInterface[^\s]* event/');

        $handler = new LaravelSqsHandler($this->getLoggerMock(), $this->tempDir);

        $handler->handle($this->getInvocationEventInterfaceMock());
    }

    public function testHandleUsesEnvironmentVariables(): void
    {
        $_ENV['YMIR_QUEUE_CONNECTION'] = 'custom_connection';
        $_ENV['YMIR_QUEUE_DELAY'] = '10';
        $_ENV['YMIR_QUEUE_TIMEOUT'] = '30';
        $_ENV['YMIR_QUEUE_TRIES'] = '5';
        $_ENV['YMIR_QUEUE_FORCE'] = '1';

        $context = $this->getInvocationContextMock();
        $context->method('getRemainingTimeInMs')->willReturn(60000);

        $event = new SqsEvent($context, [
            'Records' => [
                ['messageId' => 'id1', 'body' => '{"foo":"bar"}'],
            ],
        ]);

        $logger = $this->getLoggerMock();
        $logger->expects($this->once())
               ->method('error');

        $handler = new LaravelSqsHandler($logger, '/tmp');
        $response = $handler->handle($event);

        $this->assertSame([
            'batchItemFailures' => [
                ['itemIdentifier' => 'id1'],
            ],
        ], $response->getResponseData());

        unset($_ENV['YMIR_QUEUE_CONNECTION'], $_ENV['YMIR_QUEUE_DELAY'], $_ENV['YMIR_QUEUE_TIMEOUT'], $_ENV['YMIR_QUEUE_TRIES'], $_ENV['YMIR_QUEUE_FORCE']);
    }
}
