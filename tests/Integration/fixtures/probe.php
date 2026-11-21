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
        'session_upload_progress_enabled' => ini_get('session.upload_progress.enabled'),
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

/**
 * Write a JSON object line, a line in the Laravel bridge format and a raw line, each tagged with the given ID, to stderr.
 */
function probe_log(string $id): void
{
    $bridgeMessage = "ymir-integration-bridge-{$id}";

    file_put_contents('php://stderr', json_encode(['event' => 'ymir-integration-log', 'id' => $id])."\n");
    file_put_contents('php://stderr', "bridge-request-id\tWARNING\t{$bridgeMessage}\t".json_encode(['message' => $bridgeMessage, 'level' => 'WARNING'])."\n");
    file_put_contents('php://stderr', "ymir-integration-raw-{$id}\n");
}
