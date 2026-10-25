#!/usr/bin/env python3
"""Checks for the integration harness that do not need Docker or a built image."""

import signal
import subprocess
import unittest
from unittest import mock

import test_runtime as runtime


class HttpResponseTest(unittest.TestCase):
    def test_valid_version_specific_headers_are_preserved(self):
        cases = [
            ('1.0', 'multiValueHeaders', {'X-Test': ['first', 'second']}, {'x-test': ['first', 'second']}),
            ('2.0', 'headers', {'X-Test': 'first'}, {'x-test': ['first']}),
        ]

        for version, key, headers, expected in cases:
            with self.subTest(version=version):
                response = runtime.HttpResponse({'statusCode': 200, 'body': '{}', key: headers}, version)
                self.assertEqual(expected, response.headers)

    def test_invalid_version_specific_headers_are_rejected(self):
        cases = [
            ('v1 scalar value', '1.0', 'multiValueHeaders', {'X-Test': 'first'}),
            ('v1 nonstring list value', '1.0', 'multiValueHeaders', {'X-Test': ['first', 1]}),
            ('v2 list value', '2.0', 'headers', {'X-Test': ['first']}),
            ('v2 nonstring value', '2.0', 'headers', {'X-Test': 1}),
            ('v1 nonobject map', '1.0', 'multiValueHeaders', []),
            ('v2 nonobject map', '2.0', 'headers', []),
        ]

        for name, version, key, headers in cases:
            with self.subTest(case=name):
                with self.assertRaises(AssertionError):
                    runtime.HttpResponse({'statusCode': 200, 'body': '{}', key: headers}, version)


class LambdaContainerTest(unittest.TestCase):
    def test_interrupted_teardown_still_removes_the_container(self):
        for interrupted_action in ['logs', 'rm']:
            with self.subTest(action=interrupted_action):
                interrupted = False
                removed = []

                def run(command, **kwargs):
                    nonlocal interrupted
                    action = command[1]

                    if action == interrupted_action and not interrupted:
                        interrupted = True
                        raise runtime.Interrupted(signal.SIGTERM)

                    if action == 'rm':
                        removed.append(command[-1])

                    return subprocess.CompletedProcess(
                        command, 0, stdout=b'404' if action == 'exec' else b'', stderr=b''
                    )

                with mock.patch.dict(runtime.CONTAINERS, clear=True), mock.patch.dict(
                    runtime.LOGS, clear=True
                ), mock.patch('test_runtime.subprocess.run', side_effect=run):
                    container = runtime.LambdaContainer('harness-check', 'website')

                    with self.assertRaises(runtime.Interrupted):
                        container.remove()

                    for pending in list(runtime.CONTAINERS.values()):
                        pending.remove()

                    self.assertEqual([container.name], removed)
                    self.assertFalse(runtime.CONTAINERS)


if __name__ == '__main__':
    unittest.main(verbosity=2)
