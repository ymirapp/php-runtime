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

use AsyncAws\Lambda\LambdaClient;
use Ymir\Runtime\Exception\InvalidConfigurationException;
use Ymir\Runtime\Exception\InvalidHandlerEventException;
use Ymir\Runtime\Lambda\InvocationEvent\InvocationEventInterface;
use Ymir\Runtime\Lambda\InvocationEvent\WarmUpEvent;
use Ymir\Runtime\Lambda\Response\Http\HttpResponse;
use Ymir\Runtime\Lambda\Response\ResponseInterface;
use Ymir\Runtime\Logger;

/**
 * Lambda invocation event handler for warming up Lambda functions.
 */
class WarmUpEventHandler implements LambdaEventHandlerInterface
{
    /**
     * Lambda API client.
     *
     * @var LambdaClient
     */
    private $lambdaClient;

    /**
     * The logger that sends logs to CloudWatch.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Constructor.
     */
    public function __construct(LambdaClient $lambdaClient, Logger $logger)
    {
        $this->lambdaClient = $lambdaClient;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function canHandle(InvocationEventInterface $event): bool
    {
        return $event instanceof WarmUpEvent;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(InvocationEventInterface $event): ResponseInterface
    {
        if (!$event instanceof WarmUpEvent) {
            throw new InvalidHandlerEventException($this, $event);
        }

        $concurrency = $event->getConcurrency();

        if (1 >= $concurrency) {
            return new HttpResponse('No additional function warmed up');
        }

        $functionName = getenv('AWS_LAMBDA_FUNCTION_NAME');

        if (!is_string($functionName)) {
            throw new InvalidConfigurationException('"AWS_LAMBDA_FUNCTION_NAME" environment variable is\'t set');
        }

        $this->logger->debug(sprintf('Warming up %s additional functions', $concurrency));

        // The first Lambda function invoked will be the one running this code. So, if we want the number of concurrent
        // Lambda functions to match, we need to keep the concurrency number the same and not subtract one from it.
        for ($i = 0; $i < $concurrency; ++$i) {
            $this->lambdaClient->invoke([
                'FunctionName' => $functionName,
                'Qualifier' => 'deployed',
                'InvocationType' => 'Event',
                'LogType' => 'None',
                'Payload' => '{"ping": true}',
            ]);
        }

        return new HttpResponse('Warmed up additional functions');
    }
}
