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

namespace Ymir\Runtime\Application;

use Ymir\Runtime\Aws\LambdaClient;
use Ymir\Runtime\Lambda\Handler\ConsoleCommandLambdaEventHandler;
use Ymir\Runtime\Lambda\Handler\LambdaEventHandlerCollection;
use Ymir\Runtime\Lambda\Handler\PingLambdaEventHandler;
use Ymir\Runtime\Lambda\Handler\WarmUpEventHandler;
use Ymir\Runtime\RuntimeContext;

/**
 * Base application executed by the runtime.
 */
abstract class AbstractApplication implements ApplicationInterface
{
    /**
     * The Lambda runtime context.
     *
     * @var RuntimeContext
     */
    protected $context;

    /**
     * Constructor.
     */
    public function __construct(RuntimeContext $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function getConsoleHandlers(): LambdaEventHandlerCollection
    {
        return $this->getEventHandlerCollection([
            new ConsoleCommandLambdaEventHandler($this->context->getLogger()),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getContext(): RuntimeContext
    {
        return $this->context;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueueHandlers(): LambdaEventHandlerCollection
    {
        return $this->getEventHandlerCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(): void
    {
    }

    /**
     * Get the LambdaEventHandlerCollection with the given Lambda event handlers.
     */
    protected function getEventHandlerCollection(array $handlers = []): LambdaEventHandlerCollection
    {
        $logger = $this->context->getLogger();

        return new LambdaEventHandlerCollection($logger, array_merge([
            new PingLambdaEventHandler(),
            new WarmUpEventHandler(LambdaClient::createFromContext($this->context), $logger),
        ], $handlers));
    }
}
