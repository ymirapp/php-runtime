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

namespace Ymir\Runtime\Tests\Unit\Lambda\InvocationEvent;

use PHPUnit\Framework\TestCase;
use Tightenco\Collect\Support\Collection;
use Ymir\Runtime\Lambda\InvocationEvent\SqsEvent;
use Ymir\Runtime\Lambda\InvocationEvent\SqsRecord;
use Ymir\Runtime\Tests\Mock\InvocationContextMockTrait;

class SqsEventTest extends TestCase
{
    use InvocationContextMockTrait;

    public function testGetRecordsDefaultValue(): void
    {
        $records = (new SqsEvent($this->getInvocationContextMock()))->getRecords();

        $this->assertInstanceOf(Collection::class, $records);
        $this->assertEmpty($records);
    }

    public function testGetRecordsWithValue(): void
    {
        $records = (new SqsEvent($this->getInvocationContextMock(), ['Records' => [['foo' => 'bar']]]))->getRecords();

        $this->assertInstanceOf(Collection::class, $records);
        $this->assertCount(1, $records);
        $this->assertInstanceOf(SqsRecord::class, $records->first());
    }
}
