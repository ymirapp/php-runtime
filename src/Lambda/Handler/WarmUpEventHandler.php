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
use Ymir\Runtime\Lambda\InvocationEvent\InvocationEventInterface;
use Ymir\Runtime\Lambda\InvocationEvent\WarmUpEvent;
use Ymir\Runtime\Lambda\Response\HttpResponse;
use Ymir\Runtime\Lambda\Response\ResponseInterface;

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
     * Constructor.
     */
    public function __construct(LambdaClient $lambdaClient)
    {
        $this->lambdaClient = $lambdaClient;
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
            throw new \InvalidArgumentException('WarmUpEventHandler can only handle WarmUpEvent objects');
        }

        $concurrency = $event->getConcurrency();

        if (1 >= $concurrency) {
            return new HttpResponse('No additional function warmed up');
        }

        $functionName = getenv('AWS_LAMBDA_FUNCTION_NAME');

        if (!is_string($functionName)) {
            throw new InvalidConfigurationException('"AWS_LAMBDA_FUNCTION_NAME" environment variable is\'t set');
        }

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
