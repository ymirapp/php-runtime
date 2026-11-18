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

use Ymir\Runtime\Application\ApplicationInterface;
use Ymir\Runtime\Exception\InvalidConfigurationException;
use Ymir\Runtime\Exception\PhpFpm\PhpFpmException;
use Ymir\Runtime\Exception\PhpFpm\PhpFpmProcessException;
use Ymir\Runtime\Exception\PhpFpm\PhpFpmTimeoutException;
use Ymir\Runtime\FastCgi\PhpFpmProcess;
use Ymir\Runtime\Lambda\Handler\LambdaEventHandlerInterface;
use Ymir\Runtime\Lambda\InvocationEvent\HttpRequestEvent;
use Ymir\Runtime\Lambda\InvocationEvent\InvocationEventInterface;
use Ymir\Runtime\Lambda\Response\Http\BadGatewayHttpResponse;
use Ymir\Runtime\Lambda\Response\Http\GatewayTimeoutHttpResponse;

/**
 * Runtime for "website" functions.
 */
class WebsiteRuntime extends AbstractRuntime
{
    /**
     * The function type that the runtime handles.
     */
    public const TYPE = 'website';

    /**
     * The file where the runtime records the errors the application never sees, one JSON object per line.
     */
    private const ERRORS_FILE = '/tmp/.ymir/runtime-errors.jsonl';

    /**
     * The file size at which the runtime stops recording errors because the application doesn't report them.
     */
    private const ERRORS_FILE_MAX_SIZE = 65536;

    /**
     * The current number of invocations.
     *
     * @var int
     */
    private $invocations;

    /**
     * The maximum number of invocations.
     *
     * @var int|null
     */
    private $maxInvocations;

    /**
     * The PHP-FPM process used by the Lambda runtime.
     *
     * @var PhpFpmProcess
     */
    private $phpFpmProcess;

    public function __construct(RuntimeApiClient $client, LambdaEventHandlerInterface $handler, Logger $logger, PhpFpmProcess $phpFpmProcess, ?int $maxInvocations = null)
    {
        parent::__construct($client, $handler, $logger);

        if (is_int($maxInvocations) && $maxInvocations < 1) {
            throw new InvalidConfigurationException('"maxInvocations" must be greater than 0');
        }

        $this->invocations = 0;
        $this->maxInvocations = $maxInvocations;
        $this->phpFpmProcess = $phpFpmProcess;
    }

    /**
     * Create a "website" function runtime for the given runtime application.
     */
    public static function createFromApplication(ApplicationInterface $application): self
    {
        $context = $application->getContext();

        return new self($context->getRuntimeApiClient(), $application->getWebsiteHandlers(), $context->getLogger(), $context->getPhpFpmProcess(), $context->getMaxInvocations());
    }

    /**
     * Start the Lambda runtime.
     */
    public function start(): void
    {
        $this->phpFpmProcess->start();
    }

    /**
     * {@inheritdoc}
     */
    protected function handleEvent(InvocationEventInterface $event): void
    {
        try {
            parent::handleEvent($event);

            if (is_int($this->maxInvocations)) {
                ++$this->invocations;
            }
        } catch (PhpFpmTimeoutException $exception) {
            $this->client->sendResponse($event, new GatewayTimeoutHttpResponse($exception->getMessage()));

            $this->recordError('timeout', $event, $exception);
        } catch (PhpFpmProcessException $exception) {
            $this->logger->exception($exception);

            $this->client->sendResponse($event, new BadGatewayHttpResponse($exception->getMessage()));

            $this->recordError('crash', $event, $exception);

            $this->logger->info('PHP-FPM process has crashed, killing lambda function');

            $this->terminate(1);
        }

        if (is_int($this->maxInvocations) && $this->invocations >= $this->maxInvocations) {
            $this->logger->info(sprintf('Function has processed %s invocation events, killing lambda function', $this->maxInvocations));

            $this->terminate(0);
        }
    }

    /**
     * Record the PHP-FPM error so that the application reports it the next time it boots.
     */
    private function recordError(string $type, InvocationEventInterface $event, PhpFpmException $exception): void
    {
        if (!$event instanceof HttpRequestEvent) {
            return;
        }

        clearstatcache(true, self::ERRORS_FILE);

        if (is_file(self::ERRORS_FILE) && filesize(self::ERRORS_FILE) >= self::ERRORS_FILE_MAX_SIZE) {
            return;
        }

        $record = json_encode([
            'type' => $type,
            'message' => $exception->getMessage(),
            'method' => $event->getMethod(),
            'path' => $event->getPath(),
            'requestId' => $event->getContext()->getRequestId(),
            'timestamp' => time(),
        ], JSON_INVALID_UTF8_SUBSTITUTE);

        if (false === $record) {
            return;
        }

        file_put_contents(self::ERRORS_FILE, $record.PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
