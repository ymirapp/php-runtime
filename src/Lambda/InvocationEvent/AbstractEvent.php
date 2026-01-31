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
 * Base Lambda invocation event from the runtime API.
 */
abstract class AbstractEvent implements InvocationEventInterface
{
    /**
     * The Lambda invocation context.
     *
     * @var Context
     */
    private $context;

    /**
     * Constructor.
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function getContext(): Context
    {
        return $this->context;
    }
}
