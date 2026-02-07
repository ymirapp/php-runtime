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

use Ymir\Runtime\Lambda\Handler\Http\BedrockHttpEventHandler;
use Ymir\Runtime\Lambda\Handler\LambdaEventHandlerCollection;

/**
 * WordPress Bedrock runtime application.
 */
class BedrockApplication extends AbstractApplication
{
    /**
     * {@inheritDoc}
     */
    public static function present(string $directory): bool
    {
        return file_exists($directory.'/web/app/mu-plugins/bedrock-autoloader.php')
            || (is_dir($directory.'/web/app/') && file_exists($directory.'/web/wp-config.php') && file_exists($directory.'/config/application.php'));
    }

    /**
     * {@inheritDoc}
     */
    public function getWebsiteHandlers(): LambdaEventHandlerCollection
    {
        return $this->getEventHandlerCollection([
            new BedrockHttpEventHandler($this->context->getLogger(), $this->context->getPhpFpmProcess(), $this->context->getRootDirectory()),
        ]);
    }
}
