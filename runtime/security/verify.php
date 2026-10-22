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

use hollodotme\FastCGI\Interfaces\ProvidesResponseData;
use hollodotme\FastCGI\RequestContents\MultipartFormData;
use hollodotme\FastCGI\Requests\PostRequest;
use Symfony\Component\Process\Process;
use Ymir\Runtime\Exception\PhpFpm\PhpFpmProcessException;
use Ymir\Runtime\FastCgi\FastCgiServerClient;
use Ymir\Runtime\FastCgi\PhpFpmProcess;
use Ymir\Runtime\Logger;

// These are legacy mitigation/lifecycle regressions, not an exploit or completeness proof.
if (!is_file('/.dockerenv') || PHP_VERSION_ID < 70200 || PHP_VERSION_ID >= 80200) {
    throw new RuntimeException('Run legacy security verification only in a disposable PHP 7.2–8.1 image');
}

require '/opt/vendor/autoload.php';

/**
 * Assert even when production PHP disables native assertions.
 */
function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

/**
 * Send a real request through the runtime's FastCGI/process wrappers.
 */
function request(PhpFpmProcess $fpm, string $action = '', array $fields = [], array $files = [], array $vars = []): ProvidesResponseData
{
    $request = PostRequest::newWithRequestContent(__DIR__.'/request.php', new MultipartFormData($fields, $files));
    $request->addCustomVars(array_merge([
        'YMIR_TEST_ACTION' => $action,
        'HTTP_COOKIE' => 'PHPSESSID=ymir-security',
    ], $vars));

    return $fpm->handle($request, 5000);
}

/**
 * Decode a successful fixture response without accepting empty/crashed responses.
 */
function data(ProvidesResponseData $response): array
{
    check('' === $response->getHeaderLine('Status') || '200 OK' === $response->getHeaderLine('Status'), 'Fixture returned an HTTP error');
    $data = json_decode($response->getBody(), true);
    check(is_array($data) && isset($data['pid']), 'Fixture did not return valid JSON');

    return $data;
}

/**
 * Prove the fixture ran and whether PHP invoked its shutdown hooks.
 */
function cleanupMarkers(string $prefix, bool $expected): void
{
    check(is_file($prefix.'-ready'), 'Fixture never reached the allocation/cleanup probe: '.$prefix);
    check($expected === is_file($prefix.'-shutdown'), 'Unexpected shutdown callback behavior: '.$prefix);
    check($expected === is_file($prefix.'-destructor'), 'Unexpected destructor behavior: '.$prefix);
}

mkdir('/tmp/ymir-security');
file_put_contents('/tmp/ymir-security/upload.txt', "security upload\n");
$config = file_get_contents('/opt/ymir/etc/php-fpm.d/php-fpm.conf').<<<'FPM'

user = root
group = root
php_admin_value[memory_limit] = 32M
php_admin_flag[log_errors] = on
php_admin_flag[display_errors] = off
php_admin_flag[session.upload_progress.enabled] = on
php_admin_flag[session.upload_progress.cleanup] = off
php_admin_value[session.upload_progress.freq] = 1
php_admin_value[session.upload_progress.min_freq] = 0
FPM;
file_put_contents('/tmp/ymir-security/fpm.conf', $config);
$process = new Process(['php-fpm', '--nodaemonize', '--force-stderr', '--allow-to-run-as-root', '--fpm-config', '/tmp/ymir-security/fpm.conf', '-d', 'opcache.file_cache_only=0']);
$fpm = new PhpFpmProcess(FastCgiServerClient::createFromSocketPath('/tmp/.ymir/php-fpm.sock', 1000, 5000), new Logger(Logger::INFO), $process);
$failed = false;

try {
    $fpm->start();
    $initial = data(request($fpm, 'seed'));
    $workerPid = $initial['pid'];
    $masterPid = $process->getPid();

    $tests = [
        'native default session storage persists data' => function () use ($initial): void {
            check('' === ini_get('session.save_path') && '' === $initial['save_path'], 'CLI/FPM default session.save_path changed from the system temp directory');
            $file = sys_get_temp_dir().'/sess_ymir-security';
            check(is_file($file) && str_contains((string) file_get_contents($file), 'preserved'), 'Session data was not persisted in the default save path');
        },
        'normal and nested multipart uploads' => function () use ($fpm, $workerPid): void {
            $result = data(request($fpm, '', ['title' => 'normal', 'nested[a][b]' => 'deep', 'nested[a][c]' => 'sibling', 'items[]' => 'item'], ['document' => '/tmp/ymir-security/upload.txt', 'uploads[document]' => '/tmp/ymir-security/upload.txt']));
            check(['title' => 'normal', 'nested' => ['a' => ['b' => 'deep', 'c' => 'sibling']], 'items' => ['item']] === $result['post'], 'Multipart fields/nesting changed');
            check("security upload\n" === $result['upload'] && $result['upload'] === $result['nested_upload'], 'Flat/nested uploaded bytes were not preserved');
            check(UPLOAD_ERR_OK === $result['files']['document']['error'] && UPLOAD_ERR_OK === $result['files']['uploads']['error']['document'], 'Upload returned an error');
            check('upload.txt' === $result['files']['document']['name'] && 16 === $result['files']['document']['size'], 'Upload filename/size changed');
            check($workerPid === $result['pid'], 'Normal request unexpectedly replaced the warm worker');
        },
        'upload progress is hard-disabled with enabled=1 and cleanup=0' => function () use ($fpm): void {
            $result = data(request($fpm, '', ['PHP_SESSION_UPLOAD_PROGRESS' => 'probe'], ['document' => '/tmp/ymir-security/upload.txt']));
            check('1' === $result['progress_enabled'] && in_array($result['progress_cleanup'], ['', '0'], true), 'Upload-progress test INI settings were not applied');
            check("security upload\n" === $result['upload'], 'Progress probe did not upload a file');
            check(['sentinel' => 'preserved'] === $result['session'], 'Upload progress wrote session state despite the hard-disable');
        },
        'explicit and derived multipart body-part limits' => function () use ($fpm, $initial): void {
            check('-1' === $initial['multipart_limit'], 'Default multipart limit is missing or changed');
            foreach ([4 => 'max_multipart_body_parts=4', 5 => "max_multipart_body_parts=-1\nmax_input_vars=3\nmax_file_uploads=2"] as $limit => $ini) {
                $fields = ['one' => '1', 'two' => '2', 'three' => '3'];
                $files = ['document' => '/tmp/ymir-security/upload.txt'];
                if (5 === $limit) {
                    $files['second'] = '/tmp/ymir-security/upload.txt';
                }

                $response = request($fpm, '', $fields, $files, ['PHP_VALUE' => $ini]);
                $result = data($response);
                check($fields === $result['post'] && count($files) === count($result['files']), 'Multipart request at the limit was truncated');
                check('' === $response->getError(), 'Multipart request at the limit emitted a warning');
                check((4 === $limit ? '4' : '-1') === $result['multipart_limit'], 'Multipart limit INI entry is missing or ignored');
                $files['rejected'] = '/tmp/ymir-security/upload.txt';
                $response = request($fpm, '', $fields, $files, ['PHP_VALUE' => $ini]);
                $result = data($response);
                check(str_contains($response->getError(), 'Multipart body parts limit exceeded '.$limit), 'Missing multipart-limit rejection diagnostic');
                check(!isset($result['files']['rejected']) && count($files) - 1 === count($result['files']) && $fields === $result['post'], 'Multipart parser did not stop at the body-part limit');
            }
        },
        'request state resets on the same warm worker' => function () use ($fpm, $workerPid): void {
            $state = data(request($fpm, 'state', ['old' => 'value'], [], ['PHP_AUTH_USER' => 'probe', 'PHP_AUTH_PW' => 'synthetic', 'QUERY_STRING' => 'old=value']));
            check('probe' === $state['auth_user'] && ['old' => 'value'] === $state['get'], 'State probe was not populated');
            $clean = data(request($fpm));
            check($workerPid === $state['pid'] && $workerPid === $clean['pid'], 'State test did not reuse the original worker');
            check([] === $clean['post'] && [] === $clean['get'] && [] === $clean['files'] && null === $clean['auth_user'], 'Request data/auth leaked to the next request');
            check(!$clean['previous_error_handler'] && !$clean['previous_exception_handler'], 'Request-owned handlers leaked to the next request');
        },
        'FPM OOM bypasses cleanup, replaces child, and permits warm reuse' => function () use ($fpm, $process, $masterPid, $workerPid): void {
            data(request($fpm, 'cleanup'));
            cleanupMarkers('/tmp/ymir-security/fpm-fcgi-cleanup', true);
            $crashed = false;

            try {
                request($fpm, 'oom');
            } catch (PhpFpmProcessException $exception) {
                $crashed = 'PHP-FPM process crashed unexpectedly' === $exception->getMessage();
            }

            check($crashed, 'FPM OOM did not propagate the expected runtime crash exception');
            cleanupMarkers('/tmp/ymir-security/fpm-fcgi-oom', false);
            check($workerPid === (int) file_get_contents('/tmp/ymir-security/fpm-fcgi-oom-ready'), 'OOM did not run on the original warm worker');
            $next = data(request($fpm));
            $warm = data(request($fpm));
            check($next['pid'] !== $workerPid && $next['pid'] === $warm['pid'], 'FPM did not replace the child and reuse its replacement');
            check($masterPid === $process->getPid(), 'Recovery restarted the FPM master instead of replacing the child');
            check([] === $next['post'] && [] === $next['files'] && null === $next['auth_user'], 'Replacement worker inherited request state');
            check(['sentinel' => 'preserved'] === $next['session'], 'OOM recovery changed the persisted session');
            check(str_contains($process->getErrorOutput(), 'Allowed memory size'), 'FPM OOM diagnostic was lost');
            check(1 === preg_match('/child '.$workerPid.' exited with code 1/', $process->getErrorOutput()), 'FPM child did not hard-exit with code 1');
        },
        'CLI OOM hard-exits without cleanup and ordinary failures stay nonzero' => function (): void {
            // PHP 7.2 can reuse PHP_SAPI-specialized FPM bytecode from the shared file cache.
            $control = new Process([PHP_BINARY, '-d', 'opcache.enable_cli=0', '-d', 'memory_limit=32M', __DIR__.'/request.php', 'cleanup']);
            $control->setTimeout(10);
            check(0 === $control->run(), 'CLI cleanup control failed');
            cleanupMarkers('/tmp/ymir-security/cli-cleanup', true);
            $oom = new Process([PHP_BINARY, '-d', 'opcache.enable_cli=0', '-d', 'memory_limit=32M', __DIR__.'/request.php', 'oom']);
            $oom->setTimeout(10);
            $oom->run();
            cleanupMarkers('/tmp/ymir-security/cli-oom', false);
            check(1 === $oom->getExitCode(), 'CLI OOM did not hard-exit with code 1');
            check(str_contains($oom->getErrorOutput(), 'Allowed memory size'), 'CLI OOM diagnostic was lost');
            $failure = new Process([PHP_BINARY, '-r', 'exit(7);']);
            $failure->setTimeout(10);
            check(7 === $failure->run(), 'Ordinary CLI failure exit code changed');
        },
    ];

    foreach ($tests as $name => $test) {
        try {
            $test();
            echo '  [OK] '.$name."\n";
        } catch (Throwable $exception) {
            $failed = true;
            echo '  [FAIL] '.$name.': '.$exception->getMessage()."\n";
        }
    }
} finally {
    $fpm->stop();
}

exit($failed ? 1 : 0);
