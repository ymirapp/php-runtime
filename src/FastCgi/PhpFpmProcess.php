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

use hollodotme\FastCGI\Exceptions\ReadFailedException;
use hollodotme\FastCGI\Exceptions\TimedoutException;
use hollodotme\FastCGI\Interfaces\ProvidesRequestData;
use hollodotme\FastCGI\Interfaces\ProvidesResponseData;
use Symfony\Component\Process\Process;
use Ymir\Runtime\Exception\PhpFpm\PhpFpmProcessException;
use Ymir\Runtime\Exception\PhpFpm\PhpFpmTimeoutException;
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
            new Process(['php-fpm', '--nodaemonize', '--force-stderr', '--fpm-config', $configPath, '-d', 'opcache.file_cache_only=0'])
        );
    }

    /**
     * Handles the given request and returns the response from the PHP-FPM process.
     */
    public function handle(ProvidesRequestData $request, int $timeoutMs): ProvidesResponseData
    {
        try {
            $response = $this->client->handle($request, $timeoutMs);
        } catch (ReadFailedException $exception) {
            throw new PhpFpmProcessException('PHP-FPM process crashed unexpectedly');
        } catch (TimedoutException $exception) {
            $message = sprintf('PHP-FPM request timed out after %dms', $timeoutMs);

            $this->logger->info(sprintf('%s, restarting process', $message));

            $this->stop();
            $this->start();

            throw new PhpFpmTimeoutException($message);
        }

        // This also triggers "updateStatus" inside the Symfony process which will make it output the logs from PHP-FPM.
        if (!$this->process->isRunning()) {
            throw new PhpFpmProcessException('PHP-FPM has stopped unexpectedly');
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
        $this->process->start(function ($type, $output): void {
            $this->logger->info($output);
        });

        $this->wait(function () {
            if (!$this->process->isRunning()) {
                throw new PhpFpmProcessException('PHP-FPM process failed to start');
            }

            return !$this->isStarted();
        }, 5000000);
    }

    /**
     * Stop the PHP-FPM process.
     */
    public function stop(): void
    {
        if (!$this->process->isRunning()) {
            return;
        }

        $this->logger->info('Stopping PHP-FPM process');

        $this->process->stop();
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
     * Wait for the given callback to finish.
     */
    private function wait(callable $callback, int $timeoutUs): void
    {
        $elapsed = 0;
        $wait = 5000; // 5ms

        while ($callback()) {
            usleep($wait);

            $elapsed += $wait;

            if ($elapsed > $timeoutUs) {
                throw new PhpFpmProcessException(sprintf('PHP-FPM failed to start within %dms', $timeoutUs / 1000));
            }
        }
    }
}
