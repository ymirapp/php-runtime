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

namespace Ymir\Runtime\Tests\Unit\FastCgi;

use hollodotme\FastCGI\SocketConnections\UnixDomainSocket;
use PHPUnit\Framework\TestCase;
use Ymir\Runtime\FastCgi\FastCgiServerClient;
use Ymir\Runtime\Tests\Mock\ConfiguresSocketConnectionMockTrait;
use Ymir\Runtime\Tests\Mock\FastCgiClientMockTrait;
use Ymir\Runtime\Tests\Mock\ProvidesRequestDataMockTrait;
use Ymir\Runtime\Tests\Mock\ProvidesResponseDataMockTrait;

class FastCgiServerClientTest extends TestCase
{
    use ConfiguresSocketConnectionMockTrait;
    use FastCgiClientMockTrait;
    use ProvidesRequestDataMockTrait;
    use ProvidesResponseDataMockTrait;

    public function testCreateFromSocketPath(): void
    {
        $fastCgiServerClient = FastCgiServerClient::createFromSocketPath('/tmp/.ymir/php-fpm.sock');

        $socketConnectionReflection = (new \ReflectionObject($fastCgiServerClient))->getProperty('socketConnection');
        $socketConnectionReflection->setAccessible(true);

        $socketConnection = $socketConnectionReflection->getValue($fastCgiServerClient);

        $this->assertInstanceOf(UnixDomainSocket::class, $socketConnection);
        $this->assertSame(1000, $socketConnection->getConnectTimeout());
        $this->assertSame(900000, $socketConnection->getReadWriteTimeout());
        $this->assertSame('unix:///tmp/.ymir/php-fpm.sock', $socketConnection->getSocketAddress());
    }

    public function testHandle(): void
    {
        $connection = $this->getConfiguresSocketConnectionMock();
        $client = $this->getFastCgiClientMock();
        $request = $this->getProvidesRequestDataMock();
        $response = $this->getProvidesResponseDataMock();

        $client->expects($this->once())
                      ->method('sendAsyncRequest')
                      ->with($this->identicalTo($connection), $this->identicalTo($request))
                      ->willReturn(1);

        $client->expects($this->once())
                      ->method('readResponse')
                      ->with(1, 1000)
                      ->willReturn($response);

        $this->assertSame($response, (new FastCgiServerClient($client, $connection))->handle($request, 1000));
    }
}
