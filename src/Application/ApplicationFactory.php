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

use Ymir\Runtime\Exception\ApplicationInitializationException;
use Ymir\Runtime\RuntimeContext;

/**
 * Runtime application factory.
 */
class ApplicationFactory
{
    /**
     * Applications that the factory can create.
     *
     * ORDER MATTERS: Most specific applications must be first.
     */
    private const APPLICATIONS = [
        // WordPress
        RadicleApplication::class,
        BedrockApplication::class,
        WordPressApplication::class,

        // Laravel
        LaravelApplication::class,
    ];

    /**
     * Create the runtime application for the given runtime context.
     */
    public static function createFromContext(RuntimeContext $context): ApplicationInterface
    {
        $application = collect(self::APPLICATIONS)
            ->first(function (string $application) use ($context): bool {
                return is_a($application, ApplicationInterface::class, true) && $application::present($context->getRootDirectory());
            });

        if (!is_string($application) || !is_a($application, ApplicationInterface::class, true)) {
            throw new ApplicationInitializationException('Unable to create runtime application');
        }

        return new $application($context);
    }
}
