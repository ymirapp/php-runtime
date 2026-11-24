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

// WP-CLI never runs WP-Cron, but loading WordPress in PHP-FPM would spawn it with a loopback request.
$wp_filter = [
    'pre_get_ready_cron_jobs' => [
        10 => [
            ['function' => function (): array {
                return [];
            }, 'accepted_args' => 1],
        ],
    ],
];

require $_SERVER['YMIR_ROOT_DIRECTORY'].'/public/wp/wp-load.php';

$kernel = Roots\Acorn\Application::getInstance()->make(Illuminate\Contracts\Console\Kernel::class);

header('X-Ymir-Exit-Code: '.$kernel->call('config:cache'));

echo $kernel->output();
