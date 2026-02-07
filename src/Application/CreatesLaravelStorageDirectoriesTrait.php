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
use Ymir\Runtime\Logger;

/**
 * Creates Laravel storage directories.
 */
trait CreatesLaravelStorageDirectoriesTrait
{
    /**
     * Create the necessary Laravel storage directories.
     */
    private function createStorageDirectories(Logger $logger): void
    {
        $logger->debug('Creating Laravel storage directories');

        collect(['/bootstrap/cache', '/framework/cache', '/framework/views'])
            ->map(function (string $path): string {
                return '/tmp/storage'.$path;
            })
            ->filter(function (string $directory): bool {
                return !is_dir($directory);
            })
            ->each(function (string $directory) use ($logger): void {
                if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                    throw new ApplicationInitializationException(sprintf('Failed to create "%s" directory', $directory));
                }

                $logger->debug(sprintf('"%s" directory created', $directory));
            });
    }
}
