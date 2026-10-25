#!/usr/bin/env python3
"""Black-box tests that run a built runtime image through the AWS Lambda Runtime Interface Emulator (RIE).

Usage: test_runtime.py <image> <platform>. Run by runtime/test-image.sh. Each Lambda environment is a disposable
container that runs the image's own /opt/bootstrap under the RIE bundled with the AWS base image. Containers have
no network, a non-root Lambda-like user, a read-only root with a bounded /tmp, resource limits and the fixtures
mounted read-only as the task root.
"""

import argparse
import base64
import hashlib
import json
import os
import shutil
import signal
import subprocess
import sys
import time
import unittest
import urllib.parse
import uuid

FIXTURES = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'fixtures')
INVOKE_URL = 'http://127.0.0.1:8080/2015-03-31/functions/function/invocations'
LAMBDA_UID = 993
DOCKER_TIMEOUT = 60
INVOKE_TIMEOUT = 120
STARTUP_TIMEOUT = 60
UPLOAD_MAX_FILESIZE = 2 * 1024 * 1024
MAX_MULTIPART_BODY_PARTS = 10

# PHP 7.2-8.1 builds carry vendor EOL mitigations (Sury "Avoid state corruption via bailout" and the official
# backport commits) that _exit(1) on memory limit exhaustion, skipping shutdown and killing the PHP-FPM worker.
# See docs/research/php-official-backport-source-feasibility.md.
MEMORY_LIMIT_EXITS_PROCESS_BEFORE = 80200

IMAGE = ''
PLATFORM = ''
CONTAINERS = {}
CLEANUP_FAILURES = []
LOGS = {}


def docker(*args, stdin=None, timeout=DOCKER_TIMEOUT, check=True):
    """Run a docker command and return the completed process."""
    try:
        result = subprocess.run(['docker', *args], input=stdin, capture_output=True, timeout=timeout)
    except subprocess.TimeoutExpired:
        raise AssertionError('docker %s timed out after %ss' % (' '.join(args[:3]), timeout))

    if check and result.returncode != 0:
        raise AssertionError(
            'docker %s failed with exit code %d: %s'
            % (' '.join(args[:3]), result.returncode, result.stderr.decode(errors='replace').strip())
        )

    return result


def truncate(value, length=2000):
    value = value if isinstance(value, str) else json.dumps(value)

    return value if len(value) <= length else value[:length] + '... [truncated]'


class LambdaContainer:
    """A disposable Lambda execution environment running the image's bootstrap under RIE."""

    def __init__(self, owner, function_type, timeout=60):
        self.name = 'ymir-it-%s' % uuid.uuid4().hex[:12]
        self.owner = owner
        CONTAINERS[self.name] = self
        print('  [container %s: %s %s]' % (self.name, function_type, owner), file=sys.stderr)

        docker(
            'run',
            '--detach',
            '--pull',
            'never',
            '--name',
            self.name,
            '--platform',
            PLATFORM,
            '--network',
            'none',
            '--user',
            '%d:%d' % (LAMBDA_UID, LAMBDA_UID),
            '--read-only',
            '--tmpfs',
            '/tmp:rw,size=512m,mode=1777',
            '--memory',
            '1g',
            '--memory-swap',
            '1g',
            '--cpus',
            '1',
            '--pids-limit',
            '128',
            '--cap-drop',
            'ALL',
            '--security-opt',
            'no-new-privileges',
            '--volume',
            '%s:/var/task:ro' % FIXTURES,
            '--env',
            'AWS_REGION=us-east-1',
            '--env',
            'YMIR_FUNCTION_TYPE=%s' % function_type,
            '--env',
            'AWS_LAMBDA_FUNCTION_TIMEOUT=%d' % timeout,
            '--entrypoint',
            '/usr/local/bin/aws-lambda-rie',
            IMAGE,
            '/opt/bootstrap',
        )
        self.wait_until_listening()

    def wait_until_listening(self):
        deadline = time.monotonic() + STARTUP_TIMEOUT
        result = None

        while time.monotonic() < deadline:
            result = self.exec(
                'curl', '-s', '-o', '/dev/null', '-w', '%{http_code}', '-m', '5', 'http://127.0.0.1:8080/', check=False
            )

            if result.returncode == 0 and result.stdout.decode().isdigit() and result.stdout != b'000':
                return

            time.sleep(0.25)

        raise AssertionError(
            'RIE did not respond within %ss: %s'
            % (STARTUP_TIMEOUT, (result.stdout + result.stderr).decode(errors='replace') if result else '')
        )

    def exec(self, *command, stdin=None, timeout=DOCKER_TIMEOUT, check=True):
        return docker(
            'exec',
            '--interactive',
            '--user',
            str(LAMBDA_UID),
            self.name,
            *command,
            stdin=stdin,
            timeout=timeout,
            check=check,
        )

    def invoke(self, event):
        """Invoke the function with the given event and return the decoded Lambda payload."""
        result = self.exec(
            'curl',
            '-sS',
            '-m',
            str(INVOKE_TIMEOUT),
            '--data-binary',
            '@-',
            INVOKE_URL,
            stdin=json.dumps(event).encode(),
            timeout=INVOKE_TIMEOUT + 15,
        )

        try:
            return json.loads(result.stdout)
        except ValueError:
            raise AssertionError('Invalid Lambda payload: %s' % truncate(result.stdout.decode(errors='replace')))

    def processes(self):
        """Group the processes started under RIE (PID 1) into PHP-FPM masters, workers and the remaining runtime."""
        # Classify by kernel comm and parentage: PHP-FPM process titles aren't visible under CPU emulation.
        stats = (
            self.exec('sh', '-c', 'cat /proc/[0-9]*/stat 2>/dev/null', check=False)
            .stdout.decode(errors='replace')
            .splitlines()
        )
        parents = {}
        commands = {}

        for stat in stats:
            pid = int(stat.split(' ', 1)[0])
            commands[pid] = stat[stat.index('(') + 1 : stat.rindex(')')]
            parents[pid] = int(stat[stat.rindex(')') + 2 :].split()[1])

        def descends_from_rie(pid):
            while pid in parents and parents[pid] not in (0, 1):
                pid = parents[pid]

            return parents.get(pid) == 1

        processes = {'fpm_master': [], 'fpm_worker': [], 'runtime': []}

        for pid in sorted(pid for pid in parents if descends_from_rie(pid)):
            if commands[pid] != 'php-fpm':
                processes['runtime'].append(pid)
            elif commands.get(parents[pid]) == 'php-fpm':
                processes['fpm_worker'].append(pid)
            else:
                processes['fpm_master'].append(pid)

        return processes

    def remove(self):
        try:
            logs = docker('logs', '--tail', '80', self.name, check=False)
            LOGS.setdefault(self.owner, []).append(
                '\n'.join(
                    truncate(line, 500) for line in (logs.stdout + logs.stderr).decode(errors='replace').splitlines()
                )
            )
        except AssertionError as exception:
            LOGS.setdefault(self.owner, []).append(str(exception))

        try:
            docker('rm', '--force', self.name)
        except AssertionError as exception:
            CLEANUP_FAILURES.append(self.name)
            print(
                '  [FAIL] Unable to remove container, run "docker rm -f %s": %s' % (self.name, exception),
                file=sys.stderr,
            )

        CONTAINERS.pop(self.name, None)


class HttpResponse:
    """An API Gateway proxy response returned by the runtime."""

    def __init__(self, payload, version):
        if (
            not isinstance(payload, dict)
            or not isinstance(payload.get('statusCode'), int)
            or not isinstance(payload.get('body'), str)
        ):
            raise AssertionError('Expected an API Gateway response, got: %s' % truncate(payload))

        headers_key, other_key = (
            ('multiValueHeaders', 'headers') if '1.0' == version else ('headers', 'multiValueHeaders')
        )

        if headers_key not in payload or other_key in payload:
            raise AssertionError('Response does not use payload version %s headers: %s' % (version, truncate(payload)))

        headers = payload[headers_key]

        if not isinstance(headers, dict) or not all(isinstance(name, str) for name in headers):
            raise AssertionError('Expected an API Gateway header map, got: %s' % truncate(headers))

        if '1.0' == version:
            valid_values = all(
                isinstance(values, list) and all(isinstance(value, str) for value in values)
                for values in headers.values()
            )
        else:
            valid_values = all(isinstance(value, str) for value in headers.values())

        if not valid_values:
            raise AssertionError('Invalid payload version %s header values: %s' % (version, truncate(headers)))

        self.status = payload['statusCode']
        self.body = (
            base64.b64decode(payload['body'], validate=True)
            if payload.get('isBase64Encoded') is True
            else payload['body'].encode()
        )
        self.headers = {
            name.lower(): values if isinstance(values, list) else [values] for name, values in headers.items()
        }
        self.cookies = payload.get('cookies', []) if '2.0' == version else self.headers.get('set-cookie', [])

    def json(self, suffix=b''):
        try:
            if not self.body.endswith(suffix):
                raise ValueError

            return json.loads(self.body[: len(self.body) - len(suffix)])
        except ValueError:
            raise AssertionError(
                'Expected a JSON body (status %s), got: %s'
                % (self.status, truncate(self.body.decode(errors='replace')))
            )


def http_event(version, path, method='GET', headers=None, query=None, body=b'', cookies=None):
    """Build an API Gateway proxy event for the given payload version."""
    headers = dict({'host': 'ymir.test', 'x-forwarded-proto': 'https'}, **(headers or {}))
    cookies = cookies or []
    event = {'body': base64.b64encode(body).decode(), 'isBase64Encoded': True} if body else {'isBase64Encoded': False}

    if '1.0' == version:
        if cookies:
            headers['cookie'] = '; '.join(cookies)

        event.update(
            {
                'version': '1.0',
                'resource': '/{proxy+}',
                'path': path,
                'httpMethod': method,
                'headers': headers,
                'multiValueHeaders': {name: [value] for name, value in headers.items()},
                'queryStringParameters': query,
                'multiValueQueryStringParameters': {name: [value] for name, value in query.items()} if query else None,
                'requestContext': {
                    'httpMethod': method,
                    'path': path,
                    'protocol': 'HTTP/1.1',
                    'identity': {'sourceIp': '203.0.113.10'},
                },
            }
        )
    else:
        event.update(
            {
                'version': '2.0',
                'routeKey': '$default',
                'rawPath': path,
                'rawQueryString': urllib.parse.urlencode(query or {}),
                'headers': headers,
                'requestContext': {
                    'http': {'method': method, 'path': path, 'protocol': 'HTTP/1.1', 'sourceIp': '203.0.113.10'},
                    'routeKey': '$default',
                    'stage': '$default',
                },
            }
        )

        if query:
            event['queryStringParameters'] = query

        if cookies:
            event['cookies'] = cookies

    return event


def multipart(fields):
    """Encode (name, value) or (name, filename, bytes) parts as multipart/form-data."""
    boundary = '----ymir-%s' % uuid.uuid4().hex
    body = b''

    for field in fields:
        disposition = 'form-data; name="%s"' % field[0] + (
            '; filename="%s"\r\nContent-Type: application/octet-stream' % field[1] if len(field) == 3 else ''
        )
        content = field[-1] if isinstance(field[-1], bytes) else field[-1].encode()
        body += b'--%s\r\nContent-Disposition: %s\r\n\r\n%s\r\n' % (boundary.encode(), disposition.encode(), content)

    return body + b'--%s--\r\n' % boundary.encode(), 'multipart/form-data; boundary=%s' % boundary


def uploaded(filename, content):
    return {
        'error': 0,
        'name': filename,
        'sha256': hashlib.sha256(content).hexdigest(),
        'size': len(content),
        'tmp_dir': '/tmp',
    }


class LambdaTestCase(unittest.TestCase):
    container = None

    def http(self, path, version='2.0', **kwargs):
        return HttpResponse(self.container.invoke(http_event(version, path, **kwargs)), version)

    def identity(self, version='2.0'):
        response = self.http('/identity', version)
        self.assertEqual(200, response.status, response.body)

        return response.json()

    def assertLambdaPaths(self, identity):
        self.assertEqual(LAMBDA_UID, identity['uid'])
        self.assertEqual('/var/task', identity['cwd'])
        self.assertEqual('.:/opt/ymir/lib/php', identity['include_path'])
        self.assertEqual('', identity['session_save_path'])
        self.assertEqual('/tmp', identity['temp_dir'])
        self.assertTrue(identity['tmp_writable'])
        self.assertFalse(identity['task_root_writable'])
        self.assertIn('/var/task/php/conf.d/integration.ini', identity['scanned_ini_files'])
        self.assertEqual('512M', identity['memory_limit'], 'Customer INI must override the runtime php.ini')
        self.assertEqual(
            str(MAX_MULTIPART_BODY_PARTS),
            identity['max_multipart_body_parts'],
            'max_multipart_body_parts must be registered and configurable',
        )

    def assertWorker(self, identity, processes):
        self.assertTrue(processes['runtime'], processes)
        self.assertEqual(1, len(processes['fpm_master']), processes)
        self.assertEqual(1, len(processes['fpm_worker']), processes)
        self.assertEqual(
            processes['fpm_worker'][0], identity['pid'], 'Probe was not handled by the running PHP-FPM worker'
        )
        self.assertEqual(
            processes['fpm_master'][0], identity['ppid'], 'Probe worker is not a child of the running PHP-FPM master'
        )


class WebsiteFunctionTest(LambdaTestCase):
    """Website function behavior within a single warm Lambda environment."""

    @classmethod
    def setUpClass(cls):
        cls.container = LambdaContainer(cls.__qualname__, 'website')
        cls.cold_start = HttpResponse(cls.container.invoke(http_event('2.0', '/identity')), '2.0')
        cls.baseline = cls.container.processes()

    @classmethod
    def tearDownClass(cls):
        cls.container.remove()

    def tearDown(self):
        # Any runtime or PHP-FPM restart, even one that recovered, means warm state was lost.
        self.assertEqual(self.baseline, self.container.processes(), 'Runtime or PHP-FPM processes changed')

    def test_cold_start_and_warm_requests_reuse_the_same_processes(self):
        self.assertEqual(200, self.cold_start.status, self.cold_start.body)
        self.assertWorker(self.cold_start.json(), self.baseline)

        for version in ['1.0', '2.0', '1.0', '2.0', '2.0']:
            self.assertWorker(self.identity(version), self.baseline)

    def test_fpm_uses_lambda_paths_and_customer_ini(self):
        identity = self.identity()

        self.assertEqual('fpm-fcgi', identity['sapi'])
        self.assertLambdaPaths(identity)
        self.assertEqual('0', identity['opcache_file_cache_only'], 'PHP-FPM command-line override must win')
        self.assertEqual('2M', identity['upload_max_filesize'])
        self.assertEqual('8M', identity['post_max_size'])

    def test_request_state_does_not_leak_into_the_next_request(self):
        for version in ['1.0', '2.0']:
            with self.subTest(version=version):
                first = self.http(
                    '/state',
                    version,
                    method='POST',
                    query={'register': '1', 'secret': 'query'},
                    cookies=['secret=cookie'],
                    body=b'secret=post',
                    headers={
                        'authorization': 'Basic %s' % base64.b64encode(b'ymir:secret').decode(),
                        'content-type': 'application/x-www-form-urlencoded',
                        'x-ymir-state': 'first',
                    },
                )

                self.assertEqual(201, first.status, first.body)
                self.assertEqual(['first'], first.headers.get('x-ymir-state'))
                self.assertEqual(
                    {
                        'authorization': ['Basic eW1pcjpzZWNyZXQ=', 'ymir', 'secret'],
                        'cookie': {'secret': 'cookie'},
                        'get': {'register': '1', 'secret': 'query'},
                        'header': 'first',
                        'post': {'secret': 'post'},
                        'previous_error_handler': False,
                        'previous_exception_handler': False,
                    },
                    first.json(b'ymir-shutdown'),
                )

                second = self.http('/state', version)

                self.assertEqual(200, second.status, second.body)
                self.assertNotIn('x-ymir-state', second.headers)
                self.assertEqual(
                    {
                        'authorization': [None, None, None],
                        'cookie': [],
                        'get': [],
                        'header': None,
                        'post': [],
                        'previous_error_handler': False,
                        'previous_exception_handler': False,
                    },
                    second.json(),
                )

    def test_multipart_uploads_preserve_binary_files_and_nested_fields(self):
        files = {
            'single': ('single.bin', bytes(range(256)) * 64 + b'\r\n--not-a-boundary\r\n\x00'),
            'nested': ('nested.bin', os.urandom(300 * 1024)),
            'list-a': ('a.bin', os.urandom(4096)),
            'list-b': ('b.bin', b'\xff\xfe\x00\r\n' * 1000),
            'empty': ('empty.bin', b''),
        }

        for version in ['1.0', '2.0']:
            with self.subTest(version=version):
                body, content_type = multipart(
                    [
                        ('title', 'Ymir ✓'),
                        ('meta[author][name]', 'Carl'),
                        ('meta[tags][]', 'one'),
                        ('meta[tags][]', 'two'),
                        ('single', *files['single']),
                        ('docs[nested][deep]', *files['nested']),
                        ('docs[list][]', *files['list-a']),
                        ('docs[list][]', *files['list-b']),
                        ('empty', *files['empty']),
                    ]
                )
                response = self.http(
                    '/upload', version, method='POST', headers={'content-type': content_type}, body=body
                )

                self.assertEqual(200, response.status, response.body)
                self.assertEqual(
                    {
                        'files': {
                            'docs': {
                                'nested': {'deep': uploaded(*files['nested'])},
                                'list': [uploaded(*files['list-a']), uploaded(*files['list-b'])],
                            },
                            'empty': uploaded(*files['empty']),
                            'single': uploaded(*files['single']),
                        },
                        'post': {'meta': {'author': {'name': 'Carl'}, 'tags': ['one', 'two']}, 'title': 'Ymir ✓'},
                    },
                    response.json(),
                )

    def test_multipart_upload_over_upload_max_filesize_is_rejected_by_php(self):
        body, content_type = multipart([('field', 'kept'), ('large', 'large.bin', b'x' * (UPLOAD_MAX_FILESIZE + 1))])
        response = self.http('/upload', '1.0', method='POST', headers={'content-type': content_type}, body=body)

        self.assertEqual(200, response.status, response.body)
        self.assertEqual(
            {
                'files': {'large': {'error': 1, 'name': 'large.bin', 'sha256': None, 'size': 0, 'tmp_dir': None}},
                'post': {'field': 'kept'},
            },
            response.json(),
        )

    def test_multipart_parts_over_max_multipart_body_parts_are_dropped_by_php(self):
        body, content_type = multipart([('field%d' % index, 'value') for index in range(MAX_MULTIPART_BODY_PARTS + 2)])
        response = self.http('/upload', '2.0', method='POST', headers={'content-type': content_type}, body=body)

        self.assertEqual(200, response.status, response.body)
        self.assertEqual(
            {'post': {'field%d' % index: 'value' for index in range(MAX_MULTIPART_BODY_PARTS)}}, response.json()
        )

    def test_native_sessions_persist_across_requests(self):
        for version in ['1.0', '2.0']:
            with self.subTest(version=version):
                first = self.http('/session', version)
                cookie = next(
                    (cookie.split(';')[0] for cookie in first.cookies if cookie.startswith('PHPSESSID=')), None
                )

                self.assertEqual(200, first.status, first.body)
                self.assertIsNotNone(cookie, first.cookies)
                self.assertEqual(1, first.json()['count'])

                second = self.http('/session', version, cookies=[cookie]).json()

                self.assertEqual({'count': 2, 'id': first.json()['id'], 'pid': first.json()['pid']}, second)
                self.container.exec('test', '-s', '/tmp/sess_%s' % second['id'])

    def test_uncaught_exception_returns_500_and_worker_survives(self):
        response = self.http('/exception')

        self.assertEqual(500, response.status, response.body)
        self.assertWorker(self.identity(), self.baseline)


class WebsiteRecoveryTest(LambdaTestCase):
    """PHP-FPM failures that can change processes, each in a fresh Lambda environment."""

    def setUp(self):
        self.container = LambdaContainer(self.id(), 'website', timeout=8)
        self.before_identity = self.identity()
        self.before = self.container.processes()
        self.assertWorker(self.before_identity, self.before)

    def tearDown(self):
        self.container.remove()

    def test_memory_limit_exhaustion(self):
        response = self.http('/memory-limit')
        identity = self.identity()
        after = self.container.processes()

        if self.before_identity['php_version_id'] < MEMORY_LIMIT_EXITS_PROCESS_BEFORE:
            self.assertEqual(502, response.status, 'Mitigated PHP builds must kill the worker and take the crash path')
            self.assertNotEqual(
                self.before['runtime'], after['runtime'], 'WebsiteRuntime must terminate after a PHP-FPM crash'
            )
        else:
            self.assertEqual(500, response.status, response.body)
            self.assertEqual(self.before, after, 'A PHP fatal error must not restart the runtime or PHP-FPM')

        self.assertWorker(identity, after)

    def test_worker_crash_returns_502_and_bootstrap_restarts_runtime(self):
        response = self.http('/crash')
        identity = self.identity()
        after = self.container.processes()

        self.assertEqual(502, response.status, response.body)
        self.assertWorker(identity, after)
        self.assertNotEqual(
            self.before['runtime'], after['runtime'], 'WebsiteRuntime must terminate after a PHP-FPM crash'
        )

    def test_timeout_returns_504_and_restarts_fpm_without_restarting_runtime(self):
        response = self.http('/sleep')
        identity = self.identity()
        after = self.container.processes()

        self.assertEqual(504, response.status, response.body)
        self.assertWorker(identity, after)
        self.assertEqual(self.before['runtime'], after['runtime'])
        self.assertNotEqual(self.before['fpm_master'], after['fpm_master'])

    def test_idle_worker_replaced_by_fpm_master_recovers(self):
        self.container.exec('sh', '-c', 'kill -9 %d' % self.before['fpm_worker'][0])
        deadline = time.monotonic() + DOCKER_TIMEOUT
        replaced = self.container.processes()

        while replaced['fpm_worker'] in ([], self.before['fpm_worker']) and time.monotonic() < deadline:
            time.sleep(0.25)
            replaced = self.container.processes()

        self.assertEqual(
            self.before['fpm_master'], replaced['fpm_master'], 'PHP-FPM master must survive a worker crash'
        )
        self.assertNotEqual(
            self.before['fpm_worker'], replaced['fpm_worker'], 'PHP-FPM master did not replace the worker'
        )

        # The first invocation may fail on a connection to the dead worker, but only as a well-formed Lambda error.
        payload = self.container.invoke(http_event('2.0', '/identity'))

        if 'statusCode' in payload:
            self.assertEqual(200, HttpResponse(payload, '2.0').status, truncate(payload))
        else:
            self.assertIsInstance(payload.get('errorType'), str, truncate(payload))
            self.assertIsInstance(payload.get('errorMessage'), str, truncate(payload))

        identity = self.identity()
        self.assertWorker(identity, self.container.processes())


class ConsoleFunctionTest(LambdaTestCase):
    """Console function behavior within a single warm Lambda environment."""

    @classmethod
    def setUpClass(cls):
        cls.container = LambdaContainer(cls.__qualname__, 'console')
        cls.cold_start = cls.container.invoke({'php': 'console.php identity'})
        cls.baseline = cls.container.processes()

    @classmethod
    def tearDownClass(cls):
        cls.container.remove()

    def tearDown(self):
        self.assertEqual(self.baseline, self.container.processes(), 'Runtime processes changed')

    def command(self, event):
        payload = self.container.invoke(event)

        if not isinstance(payload, dict) or 'exitCode' not in payload or 'output' not in payload:
            raise AssertionError('Expected a console response, got: %s' % truncate(payload))

        return payload

    def identity(self, command='console.php identity'):
        response = self.command({'php': command})

        try:
            if 0 != response['exitCode']:
                raise ValueError

            return json.loads(response['output'])
        except ValueError:
            raise AssertionError('Expected identity JSON from "%s", got: %s' % (command, truncate(response)))

    def test_cold_start_and_warm_commands_reuse_the_runtime(self):
        self.assertEqual(0, self.cold_start.get('exitCode'), truncate(self.cold_start))
        self.assertTrue(self.baseline['runtime'], self.baseline)
        self.assertEqual([], self.baseline['fpm_master'], 'Console functions must not start PHP-FPM')

        for _ in range(3):
            self.assertEqual('cli', self.identity()['sapi'])

    def test_cli_uses_lambda_paths_and_customer_ini(self):
        identity = self.identity('php console.php identity')

        self.assertEqual('cli', identity['sapi'])
        self.assertLambdaPaths(identity)
        self.assertEqual('1', identity['opcache_file_cache_only'])

    def test_shell_command_output_is_returned(self):
        self.assertEqual({'exitCode': 0, 'output': 'ymir-command'}, self.command({'command': 'printf ymir-command'}))

    def test_nonzero_exit_code_is_returned(self):
        self.assertEqual(
            {'exitCode': 3, 'output': 'ymir-integration-exit'}, self.command({'php': 'console.php exit 3'})
        )

    def test_memory_limit_exhaustion_returns_fatal_exit_code(self):
        mitigated = self.identity()['php_version_id'] < MEMORY_LIMIT_EXITS_PROCESS_BEFORE
        response = self.command({'php': 'console.php memory-limit'})

        # Mitigated builds print their own message to stderr; standard builds log the fatal error with log_errors.
        expected = (
            (1, '\nFatal error: Allowed memory size of 33554432 bytes exhausted')
            if mitigated
            else (255, 'PHP Fatal error:  Allowed memory size of 33554432 bytes exhausted')
        )

        self.assertEqual(expected[0], response['exitCode'], truncate(response))
        self.assertIn(expected[1], response['output'])


def check_image():
    """Fail early with actionable messages when Docker or the image can't run the suite."""
    if not shutil.which('docker'):
        raise SystemExit('[FAIL] docker executable not found')

    if not os.path.isfile(os.path.join(FIXTURES, 'index.php')):
        raise SystemExit('[FAIL] Integration fixtures missing: %s' % FIXTURES)

    inspect = docker('image', 'inspect', '--format', '{{.Os}}/{{.Architecture}}', IMAGE, check=False)

    if inspect.returncode != 0:
        raise SystemExit('[FAIL] Image not available locally: %s' % IMAGE)
    elif inspect.stdout.decode().strip() != PLATFORM:
        raise SystemExit('[FAIL] Image platform is %s, expected %s' % (inspect.stdout.decode().strip(), PLATFORM))

    missing = docker(
        'run',
        '--rm',
        '--pull',
        'never',
        '--network',
        'none',
        '--platform',
        PLATFORM,
        '--entrypoint',
        '/bin/sh',
        IMAGE,
        '-c',
        'for f in /opt/bootstrap /usr/local/bin/aws-lambda-rie /usr/bin/curl; do [ -x "$f" ] || echo "$f"; done',
        check=False,
    )

    if missing.returncode != 0 or missing.stdout.strip():
        raise SystemExit(
            '[FAIL] Image is missing required executables: %s'
            % (missing.stdout + missing.stderr).decode(errors='replace').strip()
        )


class Interrupted(KeyboardInterrupt):
    """Raised from signal handlers because unittest records SystemExit as a test error and keeps running."""


def interrupt(signum, frame):
    raise Interrupted(signum)


def main():
    global IMAGE, PLATFORM

    parser = argparse.ArgumentParser(description=__doc__.splitlines()[0])
    parser.add_argument('image')
    parser.add_argument('platform')
    parser.add_argument(
        '-k', dest='patterns', action='append', help='only run tests matching the given unittest pattern'
    )
    arguments = parser.parse_args()
    IMAGE, PLATFORM = arguments.image, arguments.platform

    signal.signal(signal.SIGINT, interrupt)
    signal.signal(signal.SIGTERM, interrupt)

    try:
        check_image()
        loader = unittest.TestLoader()
        loader.testNamePatterns = arguments.patterns
        result = unittest.TextTestRunner(verbosity=2).run(loader.loadTestsFromModule(sys.modules[__name__]))
    except Interrupted as interruption:
        print('[FAIL] Interrupted', file=sys.stderr)

        return 128 + interruption.args[0]
    finally:
        signal.signal(signal.SIGINT, signal.SIG_IGN)
        signal.signal(signal.SIGTERM, signal.SIG_IGN)

        for container in list(CONTAINERS.values()):
            container.remove()

        if CLEANUP_FAILURES:
            print('[FAIL] Containers left running: %s' % ' '.join(CLEANUP_FAILURES), file=sys.stderr)

    failed = [test.id() for test, _ in result.failures + result.errors]

    for owner, logs in LOGS.items():
        if any(owner in test_id for test_id in failed):
            print('\n---- Lambda logs for %s ----\n%s' % (owner, '\n'.join(logs)), file=sys.stderr)

    return 0 if result.wasSuccessful() and result.testsRun > 0 and not CLEANUP_FAILURES else 1


if __name__ == '__main__':
    sys.exit(main())
