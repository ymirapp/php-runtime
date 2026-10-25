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

require __DIR__.'/probe.php';

/**
 * Describe uploaded files using the nested structure of $_FILES.
 */
function describe_uploads($names, $sizes, $errors, $tmpNames): array
{
    if (is_array($names)) {
        $uploads = [];

        foreach ($names as $key => $name) {
            $uploads[$key] = describe_uploads($name, $sizes[$key], $errors[$key], $tmpNames[$key]);
        }

        return $uploads;
    }

    return [
        'error' => $errors,
        'name' => $names,
        'sha256' => UPLOAD_ERR_OK === $errors ? hash_file('sha256', $tmpNames) : null,
        'size' => $sizes,
        'tmp_dir' => UPLOAD_ERR_OK === $errors ? dirname($tmpNames) : null,
    ];
}

$data = [];

switch (trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/')) {
    case 'identity':
        $data = probe_identity();

        break;
    case 'upload':
        foreach ($_FILES as $field => $file) {
            $data['files'][$field] = describe_uploads($file['name'], $file['size'], $file['error'], $file['tmp_name']);
        }

        $data['post'] = $_POST;

        break;
    case 'state':
        $data = [
            'authorization' => [$_SERVER['HTTP_AUTHORIZATION'] ?? null, $_SERVER['PHP_AUTH_USER'] ?? null, $_SERVER['PHP_AUTH_PW'] ?? null],
            'cookie' => $_COOKIE,
            'get' => $_GET,
            'header' => $_SERVER['HTTP_X_YMIR_STATE'] ?? null,
            'post' => $_POST,
            'previous_error_handler' => null !== set_error_handler(function (): bool {
                return false;
            }),
            'previous_exception_handler' => null !== set_exception_handler(function (): void {
            }),
        ];

        if (isset($_GET['register'])) {
            http_response_code(201);
            header('X-Ymir-State: first');
            register_shutdown_function(function (): void {
                echo 'ymir-shutdown';
            });
        }

        break;
    case 'session':
        session_start();

        $_SESSION['count'] = ($_SESSION['count'] ?? 0) + 1;
        $data = ['count' => $_SESSION['count'], 'id' => session_id(), 'pid' => getmypid()];

        break;
    case 'exception':
        throw new RuntimeException('ymir-integration-exception');
    case 'memory-limit':
        probe_exhaust_memory();

        break;
    case 'crash':
        posix_kill((int) getmypid(), 9);

        break;
    case 'sleep':
        sleep(60);

        break;
    default:
        http_response_code(404);
        $data = ['error' => 'Unknown probe'];
}

header('Content-Type: application/json');

echo json_encode($data);
