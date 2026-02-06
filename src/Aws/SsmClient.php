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

namespace Ymir\Runtime\Aws;

use AsyncAws\Ssm\SsmClient as BaseSsmClient;
use Ymir\Runtime\RuntimeContext;

class SsmClient extends BaseSsmClient
{
    /**
     * Create a new LambdaClient from the given runtime context.
     */
    public static function createFromContext(RuntimeContext $context): self
    {
        return new self(['region' => $context->getRegion()], null, null, $context->getLogger());
    }
}
