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
use Placeholder\Runtime\Lambda\LambdaResponseInterface;
use Placeholder\Runtime\Lambda\StaticFileLambdaResponse;

/**
 * Base Lambda invocation event handler.
 */
abstract class AbstractLambdaEventHandler implements LambdaEventHandlerInterface
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
    public function handle(LambdaInvocationEvent $event): LambdaResponseInterface
    {
        $filePath = $this->getEventFilePath($event);

        return $this->isStaticFile($filePath) ? new StaticFileLambdaResponse($filePath) : $this->createLambdaEventResponse($event);
    }

    /**
     * Get the file path requested by the given Lambda invocation event.
     */
    protected function getEventFilePath(LambdaInvocationEvent $event): string
    {
        return $this->rootDirectory.'/'.ltrim($event->getRequestPath(), '/');
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
    abstract protected function createLambdaEventResponse(LambdaInvocationEvent $event): LambdaResponse;
}
