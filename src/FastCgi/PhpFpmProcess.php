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
     * Path to the PHP-FPM PID file.
     *
     * @var string
     */
    private const PID_PATH = '/tmp/.ymir/php-fpm.pid';

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
        return $this->client->handle($request);
    }

    /**
     * Start the PHP-FPM process.
     */
    public function start()
    {
        if ($this->isStarted()) {
            $this->killExistingProcess();
        }

        if (!is_dir(dirname(self::SOCKET_PATH))) {
            mkdir(dirname(self::SOCKET_PATH));
        }

        $this->logger->info('Starting PHP-FPM process');

        $this->process->setTimeout(null);
        $this->process->start(function ($type, $output) {
            $this->logger->debug($output);
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
     * Kill an existing PHP-FPM process.
     */
    private function killExistingProcess()
    {
        $this->logger->info('Killing existing PHP-FPM process');

        if (!file_exists(self::PID_PATH)) {
            unlink(self::SOCKET_PATH);

            return;
        }

        $pid = (int) file_get_contents(self::PID_PATH);

        if (0 <= $pid || false === posix_getpgid($pid)) {
            $this->removeProcessFiles();

            return;
        }

        $result = posix_kill($pid, SIGTERM);

        if (false === $result) {
            $this->removeProcessFiles();

            return;
        }

        $this->wait(function () use ($pid) {
            return false !== posix_getpgid($pid);
        }, 'Timeout while waiting for PHP-FPM process to stop', 1000000);

        $this->removeProcessFiles();
    }

    /**
     * Removes all the files associated with the PHP-FPM process.
     */
    private function removeProcessFiles()
    {
        unlink(self::SOCKET_PATH);
        unlink(self::PID_PATH);
    }

    /**
     * Stop the PHP-FPM process.
     */
    private function stop()
    {
        if ($this->process->isRunning()) {
            $this->process->stop();
        }
    }

    /**
     * Wait for the given callback to finish.
     */
    private function wait(callable $callback, string $message, int $timeout)
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
