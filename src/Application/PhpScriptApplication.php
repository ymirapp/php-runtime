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

namespace Ymir\Runtime\Application;

use Ymir\Runtime\Lambda\Handler\Http\PhpScriptHttpEventHandler;
use Ymir\Runtime\Lambda\Handler\LambdaEventHandlerCollection;

/**
 * PHP script runtime application.
 */
class PhpScriptApplication extends AbstractApplication
{
    /**
     * {@inheritDoc}
     */
    public static function present(string $directory): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getWebsiteHandlers(): LambdaEventHandlerCollection
    {
        return $this->getEventHandlerCollection([
            new PhpScriptHttpEventHandler($this->context->getLogger(), $this->context->getPhpFpmProcess(), $this->context->getRootDirectory()),
        ]);
    }
}
