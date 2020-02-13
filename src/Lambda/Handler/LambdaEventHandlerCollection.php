<?php

declare(strict_types=1);

/*
 * This file is part of Placeholder PHP Runtime.
 *
 * (c) Carl Alexander <contact@carlalexander.ca>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Placeholder\Runtime\Lambda\Handler;

use Placeholder\Runtime\Lambda\LambdaInvocationEvent;
use Placeholder\Runtime\Lambda\LambdaResponseInterface;
use Placeholder\Runtime\Logger;

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
    public function canHandle(LambdaInvocationEvent $event): bool
    {
        return $this->getHandlerForEvent($event) instanceof LambdaEventHandlerInterface;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(LambdaInvocationEvent $event): LambdaResponseInterface
    {
        $handler = $this->getHandlerForEvent($event);

        if (!$handler instanceof LambdaEventHandlerInterface) {
            throw new \Exception('No handler found to handle the event');
        }

        $this->logger->info(sprintf('"%s" handler selected for the event', get_class($handler)));

        $response = $handler->handle($event);

        $this->logger->info(sprintf('"%s" handler response:', get_class($handler)), $response->getResponseData());

        return $response;
    }

    /**
     * Get the Lambda invocation event handler than can handle the given Lambda invocation event.
     */
    private function getHandlerForEvent(LambdaInvocationEvent $event): ?LambdaEventHandlerInterface
    {
        return array_reduce($this->handlers, function ($found, LambdaEventHandlerInterface $handler) use ($event) {
            return null === $found && $handler->canHandle($event) ? $handler : $found;
        });
    }
}
