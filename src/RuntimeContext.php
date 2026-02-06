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

namespace Ymir\Runtime;

use Ymir\Runtime\Exception\InvalidConfigurationException;
use Ymir\Runtime\FastCgi\PhpFpmProcess;

/**
 * The Lambda runtime context.
 */
class RuntimeContext
{
    /**
     * The Lambda root directory.
     *
     * @var string
     */
    protected $rootDirectory;

    /**
     * The Lambda runtime API client.
     *
     * @var RuntimeApiClient
     */
    protected $runtimeApiClient;
    /**
     * The logger that sends logs to CloudWatch.
     *
     * @var Logger
     */
    private $logger;

    /**
     * The maximum number of invocations.
     *
     * @var int|null
     */
    private $maxInvocations;

    /**
     * The PHP-FPM process used by the Lambda runtime.
     *
     * @var PhpFpmProcess|null
     */
    private $phpFpmProcess;

    /**
     * The AWS region that the Lambda runtime is in.
     *
     * @var string
     */
    private $region;

    /**
     * Constructor.
     */
    public function __construct(Logger $logger, RuntimeApiClient $runtimeApiClient, string $region, string $rootDirectory, ?int $maxInvocations = null, ?PhpFpmProcess $phpFpmProcess = null)
    {
        $this->logger = $logger;
        $this->maxInvocations = $maxInvocations;
        $this->phpFpmProcess = $phpFpmProcess;
        $this->region = $region;
        $this->rootDirectory = $rootDirectory;
        $this->runtimeApiClient = $runtimeApiClient;
    }

    /**
     * Create new runtime context from the Lambda environment variable.
     */
    public static function createFromEnvironment(): self
    {
        $logger = new Logger(getenv('YMIR_RUNTIME_LOG_LEVEL') ?: Logger::INFO);
        $maxInvocations = ((int) getenv('YMIR_RUNTIME_MAX_INVOCATIONS')) ?: null;
        $region = getenv('AWS_REGION');
        $rootDirectory = getenv('LAMBDA_TASK_ROOT');
        $runtimeApiUrl = getenv('AWS_LAMBDA_RUNTIME_API');

        if (!is_string($region)) {
            throw new InvalidConfigurationException('The "AWS_REGION" environment variable is missing');
        } elseif (!is_string($rootDirectory)) {
            throw new InvalidConfigurationException('The "LAMBDA_TASK_ROOT" environment variable is missing');
        } elseif (!is_string($runtimeApiUrl)) {
            throw new InvalidConfigurationException('The "AWS_LAMBDA_RUNTIME_API" environment variable is missing');
        }

        return new self($logger, new RuntimeApiClient($runtimeApiUrl, $logger), $region, $rootDirectory, $maxInvocations);
    }

    /**
     * Get the logger that sends logs to CloudWatch.
     */
    public function getLogger(): Logger
    {
        return $this->logger;
    }

    /**
     * Get the maximum number of invocations.
     */
    public function getMaxInvocations(): ?int
    {
        return $this->maxInvocations;
    }

    /**
     * Get the PHP-FPM process used by the Lambda runtime.
     */
    public function getPhpFpmProcess(): PhpFpmProcess
    {
        if (!$this->phpFpmProcess instanceof PhpFpmProcess) {
            $this->phpFpmProcess = PhpFpmProcess::createForConfig($this->logger);
        }

        return $this->phpFpmProcess;
    }

    /**
     * Get the AWS region that the Lambda runtime is in.
     */
    public function getRegion(): string
    {
        return $this->region;
    }

    /**
     * Get the Lambda root directory.
     */
    public function getRootDirectory(): string
    {
        return $this->rootDirectory;
    }

    /**
     * Get the Lambda runtime API client.
     */
    public function getRuntimeApiClient(): RuntimeApiClient
    {
        return $this->runtimeApiClient;
    }
}
