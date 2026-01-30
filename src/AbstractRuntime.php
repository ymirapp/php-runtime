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

use Ymir\Runtime\Lambda\Handler\LambdaEventHandlerInterface;
use Ymir\Runtime\Lambda\InvocationEvent\InvocationEventInterface;

/**
 * Base class for all Ymir runtimes.
 */
abstract class AbstractRuntime implements RuntimeInterface
{
    /**
     * The Lambda runtime API client.
     *
     * @var RuntimeApiClient
     */
    protected $client;

    /**
     * The collection of handlers used by the runtime to process events.
     *
     * @var LambdaEventHandlerInterface
     */
    protected $handler;

    /**
     * The logger that sends logs to CloudWatch.
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Constructor.
     */
    public function __construct(RuntimeApiClient $client, LambdaEventHandlerInterface $handler, Logger $logger)
    {
        $this->client = $client;
        $this->handler = $handler;
        $this->logger = $logger;
    }

    /**
     * Process the next Lambda runtime API event.
     */
    public function processNextEvent(): void
    {
        $event = $this->client->getNextEvent();

        try {
            $this->handleEvent($event);
        } catch (\Throwable $exception) {
            $this->logger->exception($exception);
            $this->client->sendEventError($event, $exception);
        }
    }

    /**
     * Handle the given Lambda invocation event.
     */
    protected function handleEvent(InvocationEventInterface $event): void
    {
        if (!$this->handler->canHandle($event)) {
            throw new \Exception('Unable to handle the given event');
        }

        $this->client->sendResponse($event, $this->handler->handle($event));
    }

    /**
     * Terminate the runtime.
     */
    protected function terminate(int $code): void
    {
        exit($code);
    }
}
