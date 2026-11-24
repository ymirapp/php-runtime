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
use Ymir\Runtime\Lambda\Response\ProcessResponse;
use Ymir\Runtime\Tests\Mock\ProcessMockTrait;

class ProcessResponseTest extends TestCase
{
    use ProcessMockTrait;

    public function testGetResponseData(): void
    {
        $process = $this->getProcessMock();

        $process->expects($this->once())
                ->method('getExitCode')
                ->willReturn(0);
        $process->expects($this->once())
                ->method('getOutput')
                ->willReturn('foo');

        $response = new ProcessResponse($process);

        $this->assertSame([
            'exitCode' => 0,
            'output' => 'foo',
        ], $response->getResponseData());
    }

    public function testGetResponseDataReplacesInvalidUtf8InOutput(): void
    {
        $process = $this->getProcessMock();

        $process->method('getExitCode')->willReturn(0);
        $process->method('getOutput')->willReturn("Invalid UTF-8: \xB1\x31");

        $response = new ProcessResponse($process);

        $this->assertSame([
            'exitCode' => 0,
            'output' => 'Invalid UTF-8: ?1',
        ], $response->getResponseData());
    }
}
