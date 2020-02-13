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

namespace Placeholder\Runtime\Lambda\Response;

/**
 * A response sent back to the Lambda runtime API.
 */
interface ResponseInterface
{
    /**
     * Get the response data to send back to the Lambda runtime API.
     */
    public function getResponseData(): array;
}
