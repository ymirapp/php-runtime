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

namespace Ymir\Runtime\Tests\Unit\Lambda\Response;

use PHPUnit\Framework\TestCase;
use Ymir\Runtime\Lambda\InvocationEvent\SqsRecord;
use Ymir\Runtime\Lambda\Response\SqsResponse;

class SqsResponseTest extends TestCase
{
    public function testGetResponseDataEmpty(): void
    {
        $this->assertSame(['batchItemFailures' => []], (new SqsResponse())->getResponseData());
    }

    public function testGetResponseDataWithFailures(): void
    {
        $record1 = new SqsRecord(['messageId' => 'id1']);
        $record2 = new SqsRecord(['messageId' => 'id2']);

        $this->assertSame([
            'batchItemFailures' => [
                ['itemIdentifier' => 'id1'],
                ['itemIdentifier' => 'id2'],
            ],
        ], (new SqsResponse([$record1, $record2]))->getResponseData());
    }
}
