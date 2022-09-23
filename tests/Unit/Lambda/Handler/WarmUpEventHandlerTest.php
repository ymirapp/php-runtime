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
use Ymir\Runtime\Lambda\Handler\WarmUpEventHandler;
use Ymir\Runtime\Lambda\Response\HttpResponse;
use Ymir\Runtime\Tests\Mock\FunctionMockTrait;
use Ymir\Runtime\Tests\Mock\InvocationEventInterfaceMockTrait;
use Ymir\Runtime\Tests\Mock\LambdaClientMockTrait;
use Ymir\Runtime\Tests\Mock\WarmUpEventMockTrait;

/**
 * @covers \Ymir\Runtime\Lambda\Handler\WarmUpEventHandler
 */
class WarmUpEventHandlerTest extends TestCase
{
    use FunctionMockTrait;
    use InvocationEventInterfaceMockTrait;
    use LambdaClientMockTrait;
    use WarmUpEventMockTrait;

    public function testCanHandleWarmUpEventType()
    {
        $this->assertTrue((new WarmUpEventHandler($this->getLambdaClientMock()))->canHandle($this->getWarmUpEventMock()));
    }

    public function testCanHandleWrongEventType()
    {
        $this->assertFalse((new WarmUpEventHandler($this->getLambdaClientMock()))->canHandle($this->getInvocationEventInterfaceMock()));
    }

    public function testHandleDoesntInvokeAdditionalFunctionsWhenConcurrencyIsOne()
    {
        $event = $this->getWarmUpEventMock();
        $lambdaClient = $this->getLambdaClientMock();

        $event->expects($this->once())
              ->method('getConcurrency')
              ->willReturn(1);

        $lambdaClient->expects($this->never())
                     ->method('invoke');

        $reponse = (new WarmUpEventHandler($lambdaClient))->handle($event);

        $this->assertInstanceOf(HttpResponse::class, $reponse);
        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'body' => 'H4sIAAAAAAACE/PLV0hMScksyczPS8xRSCvNSwYxFcoTi3JTUxRKCwBYiDpTIAAAAA==',
            'multiValueHeaders' => [
                'Content-Type' => ['text/html'],
                'Content-Encoding' => ['gzip'],
                'Content-Length' => [49],
            ],
        ], $reponse->getResponseData());
    }

    public function testHandleInvokesAdditionalFunctions()
    {
        $event = $this->getWarmUpEventMock();
        $getenv = $this->getFunctionMock($this->getNamespace(WarmUpEventHandler::class), 'getenv');
        $lambdaClient = $this->getLambdaClientMock();

        $event->expects($this->any())
              ->method('getConcurrency')
              ->willReturn(3);

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

        $reponse = (new WarmUpEventHandler($lambdaClient))->handle($event);

        $this->assertInstanceOf(HttpResponse::class, $reponse);
        $this->assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'body' => 'H4sIAAAAAAACEwtPLMpNTVEoLVBITEnJLMnMz0vMUUgrzUsGMYsBzXzvwx4AAAA=',
            'multiValueHeaders' => [
                'Content-Type' => ['text/html'],
                'Content-Encoding' => ['gzip'],
                'Content-Length' => [47],
            ],
        ], $reponse->getResponseData());
    }

    public function testHandleWithNoEnvironmentVariable()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('"AWS_LAMBDA_FUNCTION_NAME" environment variable is\'t set');

        $event = $this->getWarmUpEventMock();

        $event->expects($this->any())
              ->method('getConcurrency')
              ->willReturn(3);

        (new WarmUpEventHandler($this->getLambdaClientMock()))->handle($event);
    }

    public function testHandleWithWrongEventType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('WarmUpEventHandler can only handle WarmUpEvent objects');

        (new WarmUpEventHandler($this->getLambdaClientMock()))->handle($this->getInvocationEventInterfaceMock());
    }
}
