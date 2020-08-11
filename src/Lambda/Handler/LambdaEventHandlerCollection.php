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

namespace Ymir\Runtime\Lambda\Handler;

use Ymir\Runtime\Lambda\InvocationEvent\InvocationEventInterface;
use Ymir\Runtime\Lambda\Response\ResponseInterface;
use Ymir\Runtime\Logger;

/**
 * A collection of Lambda invocation event handlers.
 */
class LambdaEventHandlerCollection implements LambdaEventHandlerInterface
{
    /**
     * The Lambda invocation event handlers handled by the collection.
     *
     * @var LambdaEventHandlerInterface[]
     */
    private $handlers;

    /**
     * The logger that sends logs to CloudWatch.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Constructor.
     */
    public function __construct(Logger $logger, array $handlers = [])
    {
        $this->logger = $logger;

        foreach ($handlers as $handler) {
            $this->addHandler($handler);
        }
    }

    /**
     * Add a new Lambda invocation event handler to the collection.
     */
    public function addHandler(LambdaEventHandlerInterface $handler)
    {
        $this->handlers[] = $handler;
    }

    /**
     * {@inheritdoc}
     */
    public function canHandle(InvocationEventInterface $event): bool
    {
        return $this->getHandlerForEvent($event) instanceof LambdaEventHandlerInterface;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(InvocationEventInterface $event): ResponseInterface
    {
        $handler = $this->getHandlerForEvent($event);

        if (!$handler instanceof LambdaEventHandlerInterface) {
            throw new \RuntimeException('No handler found to handle the event');
        }

        $this->logger->debug(sprintf('"%s" handler selected for the event', get_class($handler)));

        $response = $handler->handle($event);

        $this->logger->debug(sprintf('"%s" handler response:', get_class($handler)), $response->getResponseData());

        return $response;
    }

    /**
     * Get the Lambda invocation event handler than can handle the given Lambda invocation event.
     */
    private function getHandlerForEvent(InvocationEventInterface $event): ?LambdaEventHandlerInterface
    {
        return collect($this->handlers)->first(function (LambdaEventHandlerInterface $handler) use ($event) {
            return $handler->canHandle($event);
        });
    }
}
