<?php

declare(strict_types=1);

/*
 * This file is part of Placeholder PHP Runtime.
 *
 * (c) Carl Alexander <contact@carlalexander.ca>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Placeholder\Runtime\Lambda\InvocationEvent;

/**
 * Base Lambda invocation event from the runtime API.
 */
abstract class AbstractEvent implements InvocationEventInterface
{
    /**
     * The Lambda event details.
     *
     * @var array
     */
    protected $event;

    /**
     * The ID of the Lambda invocation.
     *
     * @var string
     */
    private $id;

    /**
     * Constructor.
     */
    public function __construct(string $id, array $event = [])
    {
        $this->event = $event;
        $this->id = $id;
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): string
    {
        return $this->id;
    }
}
