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
use Placeholder\Runtime\Lambda\LambdaResponse;

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
     * Constructor.
     */
    public function __construct(array $handlers = [])
    {
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
    public function handle(LambdaInvocationEvent $event): LambdaResponse
    {
        $handler = $this->getHandlerForEvent($event);

        if (!$handler instanceof LambdaEventHandlerInterface) {
            throw new \Exception('No handler found to handle the event');
        }

        fwrite(STDERR, sprintf('"%s" handler selected for the event'.PHP_EOL, get_class($handler)));

        return $handler->handle($event);
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
