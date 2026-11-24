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

use Ymir\Runtime\FastCgi\FastCgiRequest;
use Ymir\Runtime\WebsiteRuntime;

/**
 * Creates the configuration cache with PHP-FPM so that PHP-FPM compiles the application during the cold start.
 */
trait CreatesConfigurationCacheWithPhpFpmTrait
{
    /**
     * Run the "config:cache" command with the given PHP-FPM script and return whether it succeeded.
     */
    private function createConfigurationCacheWithPhpFpm(string $script): bool
    {
        if (WebsiteRuntime::TYPE !== getenv('YMIR_FUNCTION_TYPE')) {
            return false;
        }

        $logger = $this->context->getLogger();

        try {
            $phpFpmProcess = $this->context->getPhpFpmProcess();
            $phpFpmProcess->start();

            $response = $phpFpmProcess->handle(new FastCgiRequest('', [
                'APP_RUNNING_IN_CONSOLE' => 'true',
                'REQUEST_METHOD' => 'GET',
                'SCRIPT_FILENAME' => dirname(__DIR__).'/'.$script,
                'YMIR_ROOT_DIRECTORY' => $this->context->getRootDirectory(),
            ]), 60000);
        } catch (\Throwable $exception) {
            $logger->info(sprintf('Failed to create configuration cache with PHP-FPM, falling back to the CLI: %s', $exception->getMessage()));

            return false;
        }

        if ('0' !== $response->getHeaderLine('X-Ymir-Exit-Code')) {
            $logger->info(sprintf('Failed to create configuration cache with PHP-FPM, falling back to the CLI: %s', trim($response->getBody())));

            return false;
        }

        return true;
    }
}
