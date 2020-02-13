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

use Placeholder\Runtime\Lambda\InvocationEvent\HttpRequestEvent;
use Placeholder\Runtime\Lambda\InvocationEvent\InvocationEventInterface;
use Placeholder\Runtime\Lambda\LambdaResponse;
use Placeholder\Runtime\Lambda\LambdaResponseInterface;
use Placeholder\Runtime\Lambda\StaticFileLambdaResponse;

/**
 * Base handler for HTTP request events.
 */
abstract class AbstractHttpRequestEventHandler implements LambdaEventHandlerInterface
{
    /**
     * The Lambda root directory.
     *
     * @var string
     */
    protected $rootDirectory;

    /**
     * Constructor.
     */
    public function __construct(string $rootDirectory)
    {
        $this->rootDirectory = rtrim($rootDirectory, '/');
    }

    /**
     * {@inheritdoc}
     */
    public function canHandle(InvocationEventInterface $event): bool
    {
        return $event instanceof HttpRequestEvent;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(InvocationEventInterface $event): LambdaResponseInterface
    {
        if (!$event instanceof HttpRequestEvent) {
            throw new \InvalidArgumentException(sprintf('"%s" can only handle HTTP request events', self::class));
        }

        $filePath = $this->getEventFilePath($event);

        return $this->isStaticFile($filePath) ? new StaticFileLambdaResponse($filePath) : $this->createLambdaEventResponse($event);
    }

    /**
     * Get the file path requested by the given Lambda invocation event.
     */
    protected function getEventFilePath(HttpRequestEvent $event): string
    {
        return $this->rootDirectory.'/'.ltrim($event->getPath(), '/');
    }

    /**
     * Checks if the given path is for a static file.
     */
    protected function isStaticFile(string $path): bool
    {
        return !is_dir($path) && file_exists($path);
    }

    /**
     * Create the Lambda response for the given Lambda invocation event.
     */
    abstract protected function createLambdaEventResponse(HttpRequestEvent $event): LambdaResponse;
}
