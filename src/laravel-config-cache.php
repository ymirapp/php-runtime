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

if ('fpm-fcgi' !== PHP_SAPI || !isset($_SERVER['YMIR_ROOT_DIRECTORY'])) {
    http_response_code(404);

    return;
}

require $_SERVER['YMIR_ROOT_DIRECTORY'].'/vendor/autoload.php';

$kernel = (require $_SERVER['YMIR_ROOT_DIRECTORY'].'/bootstrap/app.php')->make(Illuminate\Contracts\Console\Kernel::class);

header('X-Ymir-Exit-Code: '.$kernel->call('config:cache'));

echo $kernel->output();
