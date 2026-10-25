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

switch ($argv[1] ?? '') {
    case 'identity':
        echo json_encode(probe_identity());

        exit(0);
    case 'exit':
        echo 'ymir-integration-exit';

        exit((int) ($argv[2] ?? 1));
    case 'memory-limit':
        probe_exhaust_memory();
}

fwrite(STDERR, 'Unknown probe');

exit(64);
