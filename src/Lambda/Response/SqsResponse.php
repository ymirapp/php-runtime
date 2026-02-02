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

namespace Ymir\Runtime\Lambda\Response;

use Ymir\Runtime\Lambda\InvocationEvent\SqsRecord;

/**
 * A lambda response for an SQS event.
 */
class SqsResponse implements ResponseInterface
{
    /**
     * The SQS records that failed to be processed.
     *
     * @var SqsRecord[]
     */
    private $failedRecords;

    /**
     * Constructor.
     */
    public function __construct(array $failedRecords = [])
    {
        $this->failedRecords = $failedRecords;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseData(): array
    {
        return [
            'batchItemFailures' => array_map(function (SqsRecord $record): array {
                return ['itemIdentifier' => $record->getMessageId()];
            }, $this->failedRecords),
        ];
    }
}
