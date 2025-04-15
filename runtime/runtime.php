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

use Ymir\Runtime\Runtime;

ini_set('display_errors', '1');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

fwrite(STDERR, 'Cold start'.PHP_EOL);

require __DIR__.'/vendor/autoload.php';

fwrite(STDERR, 'Loaded runtime Composer autoload file'.PHP_EOL);

$runtime = Runtime::createFromEnvironmentVariable();
$runtime->start();

while (true) {
    $runtime->processNextEvent();
}
