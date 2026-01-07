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

use hollodotme\FastCGI\Interfaces\ProvidesRequestData;
use hollodotme\FastCGI\Interfaces\ProvidesResponseData;
use Symfony\Component\Process\Process;
use Ymir\Runtime\Logger;

/**
 * The PHP-FPM process that handles Lambda requests using FastCGI.
 */
class PhpFpmProcess
{
    /**
     * Default path to the PHP-FPM configuration file.
     *
     * @var string
     */
    private const DEFAULT_CONFIG_PATH = '/opt/ymir/etc/php-fpm.d/php-fpm.conf';

    /**
     * Path to the PHP-FPM socket file.
     *
     * @var string
     */
    private const SOCKET_PATH = '/tmp/.ymir/php-fpm.sock';

    /**
     * The FastCGI server client used to connect to the PHP-FPM process.
     *
     * @var FastCgiServerClient
     */
    private $client;

    /**
     * The CloudWatch logger.
     *
     * @var Logger
     */
    private $logger;

    /**
     * The PHP-FPM process.
     *
     * @var Process
     */
    private $process;

    /**
     * Constructor.
     */
    public function __construct(FastCgiServerClient $client, Logger $logger, Process $process)
    {
        $this->client = $client;
        $this->logger = $logger;
        $this->process = $process;
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->stop();
    }

    /**
     * Create a new PHP-FPM process for the given configuration file.
     */
    public static function createForConfig(Logger $logger, string $configPath = self::DEFAULT_CONFIG_PATH): self
    {
        return new self(
            FastCgiServerClient::createFromSocketPath(self::SOCKET_PATH),
            $logger,
            new Process(['php-fpm', '--nodaemonize', '--force-stderr', '--fpm-config', $configPath])
        );
    }

    /**
     * Handles the given request and returns the response from the PHP-FPM process.
     */
    public function handle(ProvidesRequestData $request): ProvidesResponseData
    {
        $response = $this->client->handle($request);

        // This also triggers "updateStatus" inside the Symfony process which will make it output the logs from PHP-FPM.
        if (!$this->process->isRunning()) {
            throw new \Exception('PHP-FPM has stopped unexpectedly');
        }

        return $response;
    }

    /**
     * Start the PHP-FPM process.
     */
    public function start(): void
    {
        $socketDirectory = dirname(self::SOCKET_PATH);

        if (!is_dir($socketDirectory)) {
            mkdir($socketDirectory);
        }

        $this->logger->info('Starting PHP-FPM process');

        $this->process->setTimeout(null);
        $this->process->start(function ($type, $output) {
            $this->logger->info($output);
        });

        $this->wait(function () {
            if (!$this->process->isRunning()) {
                throw new \Exception('PHP-FPM process failed to start');
            }

            return !$this->isStarted();
        }, 'Timeout while waiting for PHP-FPM process to start', 5000000);
    }

    /**
     * Checks if the PHP-FPM process is started.
     */
    private function isStarted(): bool
    {
        clearstatcache(false, self::SOCKET_PATH);

        return file_exists(self::SOCKET_PATH);
    }

    /**
     * Stop the PHP-FPM process.
     */
    private function stop(): void
    {
        if ($this->process->isRunning()) {
            $this->process->stop();
        }
    }

    /**
     * Wait for the given callback to finish.
     */
    private function wait(callable $callback, string $message, int $timeout): void
    {
        $elapsed = 0;
        $wait = 5000; // 5ms

        while ($callback()) {
            usleep($wait);

            $elapsed += $wait;

            if ($elapsed > $timeout) {
                throw new \Exception($message);
            }
        }
    }
}
