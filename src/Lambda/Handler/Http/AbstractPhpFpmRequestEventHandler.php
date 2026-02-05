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

use Ymir\Runtime\FastCgi\FastCgiRequest;
use Ymir\Runtime\FastCgi\PhpFpmProcess;
use Ymir\Runtime\Lambda\InvocationEvent\HttpRequestEvent;
use Ymir\Runtime\Lambda\Response\Http\FastCgiHttpResponse;
use Ymir\Runtime\Lambda\Response\Http\HttpResponse;
use Ymir\Runtime\Logger;

/**
 * Base Lambda invocation event handler for handlers that use PHP-FPM.
 */
abstract class AbstractPhpFpmRequestEventHandler extends AbstractHttpRequestEventHandler
{
    /**
     * The logger that sends logs to CloudWatch.
     *
     * @var Logger
     */
    private $logger;

    /**
     * The PHP-FPM process.
     *
     * @var PhpFpmProcess
     */
    private $process;

    /**
     * Constructor.
     */
    public function __construct(Logger $logger, PhpFpmProcess $process, string $rootDirectory)
    {
        parent::__construct($rootDirectory);

        $this->logger = $logger;
        $this->process = $process;
    }

    /**
     * {@inheritdoc}
     */
    protected function createLambdaEventResponse(HttpRequestEvent $event): HttpResponse
    {
        $request = FastCgiRequest::createFromInvocationEvent($event, $this->getScriptFilePath($event));
        $timeoutMs = max(1000, $event->getContext()->getRemainingTimeInMs() - 1000);

        $this->logger->debug('FastCGI request sent', [
            'content' => $request->getContent(),
            'parameters' => $request->getParams(),
        ]);

        return new FastCgiHttpResponse($this->process->handle($request, $timeoutMs), $event->getPayloadVersion(), in_array('gzip', $request->getAcceptableEncodings()));
    }

    /**
     * {@inheritdoc}
     */
    protected function isStaticFile(string $path): bool
    {
        return parent::isStaticFile($path) && false === stripos($path, '.php');
    }

    /**
     * Get the path to script file to pass to PHP-FPM based on the Lambda invocation event.
     */
    abstract protected function getScriptFilePath(HttpRequestEvent $event): string;
}
