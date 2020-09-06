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

/**
 * Lambda invocation event for a warm up request.
 */
class WarmUpEvent extends AbstractEvent
{
    /**
     * The number of concurrent Lambda functions to keep warm.
     *
     * @var int
     */
    private $concurrency;

    /**
     * Constructor.
     */
    public function __construct(string $id, int $concurrency)
    {
        parent::__construct($id);

        $this->concurrency = $concurrency;
    }

    /**
     * Get the number of concurrent Lambda functions to keep warm.
     */
    public function getConcurrency(): int
    {
        return $this->concurrency;
    }
}
