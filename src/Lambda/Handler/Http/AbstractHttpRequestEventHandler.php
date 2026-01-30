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

namespace Ymir\Runtime\Lambda\Handler\Http;

use Ymir\Runtime\Lambda\Handler\LambdaEventHandlerInterface;
use Ymir\Runtime\Lambda\InvocationEvent\HttpRequestEvent;
use Ymir\Runtime\Lambda\InvocationEvent\InvocationEventInterface;
use Ymir\Runtime\Lambda\Response\HttpResponse;
use Ymir\Runtime\Lambda\Response\NotFoundHttpResponse;
use Ymir\Runtime\Lambda\Response\ResponseInterface;
use Ymir\Runtime\Lambda\Response\StaticFileResponse;

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
    public function handle(InvocationEventInterface $event): ResponseInterface
    {
        if (!$event instanceof HttpRequestEvent || !$this->canHandle($event)) {
            throw new \InvalidArgumentException(sprintf('%s cannot handle the given invocation event object', (new \ReflectionClass(static::class))->getShortName()));
        }

        $filePath = $this->getEventFilePath($event);

        if (!$this->isPubliclyAccessible($filePath)) {
            return new NotFoundHttpResponse();
        }

        return $this->isStaticFile($filePath) ? new StaticFileResponse($filePath) : $this->createLambdaEventResponse($event);
    }

    /**
     * Get the file path requested by the given Lambda invocation event.
     */
    protected function getEventFilePath(HttpRequestEvent $event): string
    {
        return $this->rootDirectory.'/'.ltrim($event->getPath(), '/');
    }

    /**
     * Checks if the given file path is publicly accessible.
     */
    protected function isPubliclyAccessible(string $filePath): bool
    {
        return true;
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
    abstract protected function createLambdaEventResponse(HttpRequestEvent $event): HttpResponse;
}
