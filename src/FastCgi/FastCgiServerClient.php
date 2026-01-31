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

namespace Ymir\Runtime\FastCgi;

use hollodotme\FastCGI\Client;
use hollodotme\FastCGI\Interfaces\ConfiguresSocketConnection;
use hollodotme\FastCGI\Interfaces\ProvidesRequestData;
use hollodotme\FastCGI\Interfaces\ProvidesResponseData;
use hollodotme\FastCGI\SocketConnections\UnixDomainSocket;

/**
 * A client that connects to a FastCGI server.
 */
class FastCgiServerClient
{
    /**
     * The FastCGI client used to interact with the PHP-FPM process.
     *
     * @var Client
     */
    private $client;

    /**
     * The FastCGI socket connection.
     *
     * @var ConfiguresSocketConnection
     */
    private $socketConnection;

    /**
     * Constructor.
     */
    public function __construct(Client $client, ConfiguresSocketConnection $socketConnection)
    {
        $this->client = $client;
        $this->socketConnection = $socketConnection;
    }

    /**
     * Create a FastCGI client for the given unix socket path.
     */
    public static function createFromSocketPath(string $socketPath, int $connectTimeout = 1000, int $readWriteTimeout = 900000): self
    {
        return new self(new Client(), new UnixDomainSocket($socketPath, $connectTimeout, $readWriteTimeout));
    }

    /**
     * Handles the given request and return the response from the FastCGI socket.
     */
    public function handle(ProvidesRequestData $request, int $timeoutMs): ProvidesResponseData
    {
        return $this->client->readResponse($this->client->sendAsyncRequest($this->socketConnection, $request), $timeoutMs);
    }
}
