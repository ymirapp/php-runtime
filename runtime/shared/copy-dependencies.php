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
 * This script is adapted from Bref's copy-dependencies.php
 *
 * It finds all shared library dependencies of a set of binaries/libraries
 * and copies them to a target directory. It is recursive to ensure all
 * transient dependencies are captured.
 */

if ($argc < 3) {
    echo "Usage: php copy-dependencies.php <source_dir_or_file> <target_lib_dir>\n";
    exit(1);
}

$source = $argv[1];
$targetLibDir = $argv[2];

if (!is_dir($targetLibDir)) {
    mkdir($targetLibDir, 0755, true);
}

// Libraries that are already present in the AWS Lambda AL2023 base image.
// Skipping these saves significant space.
$skipList = [
    'libaudit.so', 'libcap.so', 'libcap-ng.so', 'libcom_err.so', 
    'libcrypto.so.3', 'libdb-5.3.so', 'libdl.so', 'libffi.so', 'libgcc_s.so',
    'libgdbm.so', 'libgdbm_compat.so', 'libglib-2.0.so', 'libgmodule-2.0.so',
    'libgthread-2.0.so', 'libhistory.so', 'libk5crypto.so', 'libkeyutils.so',
    'libkrb5.so', 'libkrb5support.so', 'libm.so', 'libc.so', 'libncurses.so', 
    'libncursesw.so', 'libnsl.so', 'libp11-kit.so', 'libpam.so', 'libpam_misc.so', 
    'libpcre.so', 'libpcre2-8.so', 'libpthread.so', 'libreadline.so', 
    'libresolv.so', 'librt.so', 'libselinux.so', 'libsepol.so', 'libstdc++.so', 
    'libtinfo.so', 'libutil.so', 'libz.so', 'linux-vdso.so', 
    'ld-linux-x86-64.so', 'ld-linux-aarch64.so',
];

/**
 * Get all shared library dependencies of a file.
 */
function get_dependencies(string $path): array {
    if (!is_file($path)) {
        return [];
    }
    
    // Check if it's a binary or shared library
    $fileInfo = shell_exec("file -b " . escapeshellarg($path));
    if (false === $fileInfo || strpos($fileInfo, 'ELF') === false) {
        return [];
    }

    $output = shell_exec("ldd " . escapeshellarg($path));
    if (empty($output)) {
        return [];
    }

    $lines = explode("\n", $output);
    $libs = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;

        if (preg_match('/=>\s+([^\s]+)/', $line, $matches)) {
            $libs[] = $matches[1];
        } elseif (preg_match('/^\s*([^\s]+\.so[^\s]*)/', $line, $matches)) {
            $libs[] = $matches[1];
        }
    }

    global $skipList;

    return array_filter($libs, function($lib) use ($skipList) {
        if (!file_exists($lib)) {
            return false;
        }

        $libName = basename($lib);
        foreach ($skipList as $skip) {
            if (strpos($libName, $skip) === 0) {
                return false;
            }
        }

        return (strpos($lib, '/lib64') === 0 || 
                strpos($lib, '/lib') === 0 ||
                strpos($lib, '/usr/lib64') === 0 ||
                strpos($lib, '/usr/lib') === 0 ||
                strpos($lib, '/opt/ymir/lib') === 0);
    });
}

$toProcess = [];
if (is_dir($source)) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source));
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $toProcess[] = $file->getPathname();
        }
    }
} else {
    $toProcess[] = $source;
}

$processed = [];

while (!empty($toProcess)) {
    $file = array_shift($toProcess);
    
    if (isset($processed[$file])) {
        continue;
    }
    $processed[$file] = true;

    $deps = get_dependencies($file);
    foreach ($deps as $dep) {
        $libName = basename($dep);
        $targetPath = $targetLibDir . '/' . $libName;
        
        if (!file_exists($targetPath)) {
            echo "Copying $dep to $targetPath\n";
            copy($dep, $targetPath);
            $toProcess[] = $targetPath;
        }
    }
}
