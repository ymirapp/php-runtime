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
use Ymir\Runtime\Exception\InvalidConfigurationException;
use Ymir\Runtime\Lambda\Handler\WarmUpEventHandler;
use Ymir\Runtime\Lambda\Response\Http\HttpResponse;
use Ymir\Runtime\Tests\Mock\FunctionMockTrait;
use Ymir\Runtime\Tests\Mock\InvocationEventInterfaceMockTrait;
use Ymir\Runtime\Tests\Mock\LambdaClientMockTrait;
use Ymir\Runtime\Tests\Mock\LoggerMockTrait;
use Ymir\Runtime\Tests\Mock\WarmUpEventMockTrait;

class WarmUpEventHandlerTest extends TestCase
{
    use FunctionMockTrait;
    use InvocationEventInterfaceMockTrait;
    use LambdaClientMockTrait;
    use LoggerMockTrait;
    use WarmUpEventMockTrait;

    public function testCanHandleWarmUpEventType(): void
    {
        $this->assertTrue((new WarmUpEventHandler($this->getLambdaClientMock(), $this->getLoggerMock()))->canHandle($this->getWarmUpEventMock()));
    }

    public function testCanHandleWrongEventType(): void
    {
        $this->assertFalse((new WarmUpEventHandler($this->getLambdaClientMock(), $this->getLoggerMock()))->canHandle($this->getInvocationEventInterfaceMock()));
    }

    public function testHandleDoesntInvokeAdditionalFunctionsWhenConcurrencyIsOne(): void
    {
        $event = $this->getWarmUpEventMock();
        $lambdaClient = $this->getLambdaClientMock();

        $event->expects($this->once())
              ->method('getConcurrency')
              ->willReturn(1);

        $lambdaClient->expects($this->never())
                     ->method('invoke');

        $reponse = (new WarmUpEventHandler($lambdaClient, $this->getLoggerMock()))->handle($event);

        $this->assertInstanceOf(HttpResponse::class, $reponse);
        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'body' => base64_encode('No additional function warmed up'),
            'multiValueHeaders' => [
                'Content-Type' => ['text/html'],
            ],
        ], $reponse->getResponseData());
    }

    public function testHandleInvokesAdditionalFunctions(): void
    {
        $event = $this->getWarmUpEventMock();
        $getenv = $this->getFunctionMock($this->getNamespace(WarmUpEventHandler::class), 'getenv');
        $lambdaClient = $this->getLambdaClientMock();
        $logger = $this->getLoggerMock();

        $event->expects($this->any())
              ->method('getConcurrency')
              ->willReturn(3);

        $logger->expects($this->once())
               ->method('debug')
               ->with($this->identicalTo('Warming up 3 additional functions'));

        $getenv->expects($this->once())
               ->with($this->identicalTo('AWS_LAMBDA_FUNCTION_NAME'))
               ->willReturn('function-name');

        $lambdaClient->expects($this->exactly(3))
                     ->method('invoke')
                     ->with([
                         'FunctionName' => 'function-name',
                         'Qualifier' => 'deployed',
                         'InvocationType' => 'Event',
                         'LogType' => 'None',
                          'Payload' => '{"ping": true}',
                      ]);

        $reponse = (new WarmUpEventHandler($lambdaClient, $logger))->handle($event);

        $this->assertInstanceOf(HttpResponse::class, $reponse);
        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'body' => base64_encode('Warmed up additional functions'),
            'multiValueHeaders' => [
                'Content-Type' => ['text/html'],
            ],
        ], $reponse->getResponseData());
    }

    public function testHandleWithNoEnvironmentVariable(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('"AWS_LAMBDA_FUNCTION_NAME" environment variable is\'t set');

        $event = $this->getWarmUpEventMock();

        $event->expects($this->any())
              ->method('getConcurrency')
              ->willReturn(3);

        (new WarmUpEventHandler($this->getLambdaClientMock(), $this->getLoggerMock()))->handle($event);
    }

    public function testHandleWithWrongEventType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('WarmUpEventHandler can only handle WarmUpEvent objects');

        (new WarmUpEventHandler($this->getLambdaClientMock(), $this->getLoggerMock()))->handle($this->getInvocationEventInterfaceMock());
    }
}
