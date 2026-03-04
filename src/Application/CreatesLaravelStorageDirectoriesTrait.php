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

/**
 * Creates Laravel storage directories.
 */
trait CreatesLaravelStorageDirectoriesTrait
{
    /**
     * Create the necessary Laravel storage directories.
     */
    private function createStorageDirectories(): void
    {
        collect(['/bootstrap/cache', '/framework/cache', '/framework/views'])
            ->map(function (string $path): string {
                return '/tmp/storage'.$path;
            })
            ->filter(function (string $directory): bool {
                return !is_dir($directory);
            })
            ->each(function (string $directory): void {
                if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                    throw new ApplicationInitializationException(sprintf('Failed to create "%s" directory', $directory));
                }
            });
    }
}
