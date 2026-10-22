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

if (!is_file('/.dockerenv')) {
    throw new RuntimeException('Security fixtures must run inside a disposable test container');
}

$action = 'cli' === PHP_SAPI ? ($argv[1] ?? '') : ($_SERVER['YMIR_TEST_ACTION'] ?? '');

if (in_array($action, ['cleanup', 'oom'], true)) {
    $marker = '/tmp/ymir-security/'.PHP_SAPI.'-'.$action;
    register_shutdown_function(function () use ($marker): void {
        file_put_contents($marker.'-shutdown', 'called');
    });
    $probe = new class() {
        /**
         * Path prefix for cleanup markers.
         *
         * @var string
         */
        public $marker;

        public function __destruct()
        {
            file_put_contents($this->marker.'-destructor', 'called');
        }
    };
    $probe->marker = $marker;
    file_put_contents($marker.'-ready', (string) getmypid());

    if ('oom' === $action) {
        $allocation = str_repeat('x', 64 * 1024 * 1024);

        throw new RuntimeException('OOM allocation unexpectedly succeeded');
    }
}

if ('cli' === PHP_SAPI) {
    exit(0);
}

$previousErrorHandler = set_error_handler(function (): bool { return false; });
restore_error_handler();
$previousExceptionHandler = set_exception_handler(function (Throwable $exception): void {});
restore_exception_handler();

if ('state' === $action) {
    set_error_handler(function (): bool { return true; });
    set_exception_handler(function (Throwable $exception): void {});
}

session_start();
if ('seed' === $action) {
    $_SESSION['sentinel'] = 'preserved';
}

$session = $_SESSION;
session_write_close();

header('Content-Type: application/json');
echo json_encode([
    'pid' => getmypid(),
    'post' => $_POST,
    'get' => $_GET,
    'files' => $_FILES,
    'upload' => isset($_FILES['document']['tmp_name']) && is_uploaded_file($_FILES['document']['tmp_name']) ? file_get_contents($_FILES['document']['tmp_name']) : null,
    'nested_upload' => isset($_FILES['uploads']['tmp_name']['document']) && is_uploaded_file($_FILES['uploads']['tmp_name']['document']) ? file_get_contents($_FILES['uploads']['tmp_name']['document']) : null,
    'session' => $session,
    'save_path' => ini_get('session.save_path'),
    'progress_enabled' => ini_get('session.upload_progress.enabled'),
    'progress_cleanup' => ini_get('session.upload_progress.cleanup'),
    'multipart_limit' => ini_get('max_multipart_body_parts'),
    'auth_user' => $_SERVER['PHP_AUTH_USER'] ?? null,
    'previous_error_handler' => null !== $previousErrorHandler,
    'previous_exception_handler' => null !== $previousExceptionHandler,
]);
