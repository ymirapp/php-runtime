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

use hollodotme\FastCGI\Exceptions\ConnectException;
use hollodotme\FastCGI\Exceptions\ReadFailedException;
use hollodotme\FastCGI\Exceptions\TimedoutException;
use hollodotme\FastCGI\Interfaces\ProvidesRequestData;
use hollodotme\FastCGI\Interfaces\ProvidesResponseData;
use Symfony\Component\Process\Process;
use Ymir\Runtime\Exception\PhpFpm\PhpFpmProcessException;
use Ymir\Runtime\Exception\PhpFpm\PhpFpmTimeoutException;
use Ymir\Runtime\Logger;
use Ymir\Runtime\ProcessOutputLogger;

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
     * Default directory of the opcache file cache.
     *
     * @var string
     */
    private const DEFAULT_OPCACHE_FILE_CACHE = '/tmp';

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
     * The logger that sends logs to CloudWatch.
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
     * The logger for the output of the PHP-FPM process.
     *
     * @var ProcessOutputLogger
     */
    private $processOutput;

    /**
     * Constructor.
     */
    public function __construct(FastCgiServerClient $client, Logger $logger, Process $process)
    {
        $this->client = $client;
        $this->logger = $logger;
        $this->process = $process;
        $this->processOutput = new ProcessOutputLogger($logger);
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
        $command = ['php-fpm', '--nodaemonize', '--force-stderr', '--fpm-config', $configPath, '-d', 'opcache.file_cache_only=0'];

        if (self::DEFAULT_OPCACHE_FILE_CACHE === ini_get('opcache.file_cache')) {
            $command = array_merge($command, ['-d', 'opcache.file_cache=']);
        }

        return new self(FastCgiServerClient::createFromSocketPath(self::SOCKET_PATH), $logger, new Process($command));
    }

    /**
     * Handles the given request and returns the response from the PHP-FPM process.
     */
    public function handle(ProvidesRequestData $request, int $timeoutMs): ProvidesResponseData
    {
        $hasRetriedAfterConnectException = false;

        while (true) {
            try {
                return $this->handleRequest($request, $timeoutMs);
            } catch (ConnectException $exception) {
                $message = 'Unable to connect to PHP-FPM FastCGI socket';

                if (true === $hasRetriedAfterConnectException) {
                    $this->logger->info(sprintf('%s after retry', $message));

                    throw new PhpFpmProcessException($message);
                }

                $this->logger->info(sprintf('%s, restarting process and retrying request', $message));

                $this->restart();

                $hasRetriedAfterConnectException = true;
            } catch (PhpFpmTimeoutException $exception) {
                $this->restartAfterTimeout();

                throw $exception;
            }
        }
    }

    /**
     * Start the PHP-FPM process.
     */
    public function start(): void
    {
        $start = microtime(true);
        $socketDirectory = dirname(self::SOCKET_PATH);

        if (!is_dir($socketDirectory)) {
            mkdir($socketDirectory);
        }

        $this->logger->info('Starting PHP-FPM process');

        $this->process->setTimeout(null);
        $this->process->start(function ($type, $output): void {
            $this->processOutput->log($output);
        });

        while (!$this->isStarted()) {
            if (!$this->process->isRunning()) {
                throw new PhpFpmProcessException('PHP-FPM process failed to start');
            }

            usleep(5000);
        }

        $this->logger->info(sprintf('PHP-FPM process started in %dms', (microtime(true) - $start) * 1000));
    }

    /**
     * Stop the PHP-FPM process.
     */
    public function stop(): void
    {
        if ($this->process->isRunning()) {
            $this->logger->info('Stopping PHP-FPM process');

            $this->process->stop();
        }

        $this->processOutput->flush();
    }

    /**
     * Handle the request with the PHP-FPM process.
     */
    private function handleRequest(ProvidesRequestData $request, int $timeoutMs): ProvidesResponseData
    {
        try {
            $response = $this->client->handle($request, $timeoutMs);
        } catch (ReadFailedException $exception) {
            throw new PhpFpmProcessException('PHP-FPM process crashed unexpectedly');
        } catch (TimedoutException $exception) {
            $message = sprintf('PHP-FPM request timed out after %dms', $timeoutMs);

            $this->logger->info($message);

            throw new PhpFpmTimeoutException($message);
        }

        // This also triggers "updateStatus" inside the Symfony process which will make it output the logs from PHP-FPM.
        if (!$this->process->isRunning()) {
            throw new PhpFpmProcessException('PHP-FPM has stopped unexpectedly');
        }

        // PHP-FPM writes headers for every request it runs. Empty output means the worker failed to start the request and exited.
        if ('' === $response->getOutput()) {
            throw new PhpFpmProcessException('PHP-FPM ended the request without a response');
        }

        return $response;
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
     * Remove the PHP-FPM socket file.
     */
    private function removeSocketFile(): void
    {
        clearstatcache(false, self::SOCKET_PATH);

        if (file_exists(self::SOCKET_PATH)) {
            unlink(self::SOCKET_PATH);
        }
    }

    /**
     * Restart the PHP-FPM process.
     */
    private function restart(): void
    {
        $this->logger->info('Restarting PHP-FPM process');

        $this->stop();
        $this->removeSocketFile();
        $this->start();
    }

    /**
     * Attempt to restart PHP-FPM after a timeout without changing the request outcome.
     */
    private function restartAfterTimeout(): void
    {
        try {
            $this->restart();
        } catch (\Throwable $exception) {
            $this->logger->info(sprintf('Failed to restart PHP-FPM process after timeout: %s', $exception->getMessage()));
        }
    }
}
