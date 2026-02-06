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

namespace Ymir\Runtime\Lambda\InvocationEvent;

use Tightenco\Collect\Support\Enumerable;

/**
 * Lambda invocation event for an SQS message.
 *
 * @see https://docs.aws.amazon.com/lambda/latest/dg/with-sqs.html
 */
class SqsEvent extends AbstractEvent
{
    /**
     * The Lambda event details.
     *
     * @var array
     */
    private $event;

    /**
     * Constructor.
     */
    public function __construct(InvocationContext $context, array $event = [])
    {
        parent::__construct($context);

        $this->event = $event;
    }

    /**
     * Get the records in the SQS event.
     */
    public function getRecords(): Enumerable
    {
        return collect($this->event['Records'] ?? [])->map(function (array $record) {
            return new SqsRecord($record);
        });
    }
}
