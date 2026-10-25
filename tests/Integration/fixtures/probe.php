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

/**
 * Describe the PHP process and configuration that handled the probe.
 */
function probe_identity(): array
{
    $tempFile = tempnam(sys_get_temp_dir(), 'ymir');
    $tmpWritable = is_string($tempFile) && 4 === file_put_contents($tempFile, 'ymir');

    if (is_string($tempFile)) {
        unlink($tempFile);
    }

    return [
        'cwd' => getcwd(),
        'include_path' => ini_get('include_path'),
        'max_multipart_body_parts' => ini_get('max_multipart_body_parts'),
        'memory_limit' => ini_get('memory_limit'),
        'opcache_file_cache_only' => ini_get('opcache.file_cache_only'),
        'pid' => getmypid(),
        'php_version_id' => PHP_VERSION_ID,
        'post_max_size' => ini_get('post_max_size'),
        'ppid' => posix_getppid(),
        'sapi' => PHP_SAPI,
        'scanned_ini_files' => array_map('trim', explode(',', (string) php_ini_scanned_files())),
        'session_save_path' => ini_get('session.save_path'),
        'task_root_writable' => is_writable('/var/task'),
        'temp_dir' => sys_get_temp_dir(),
        'tmp_writable' => $tmpWritable,
        'uid' => posix_getuid(),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
    ];
}

/**
 * Allocate memory until PHP stops the script for exceeding a 32M memory_limit.
 */
function probe_exhaust_memory(): void
{
    ini_set('memory_limit', '32M');

    $chunks = [];

    while (true) {
        $chunks[] = str_repeat((string) count($chunks), 8 * 1024 * 1024);
    }
}
