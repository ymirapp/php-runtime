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

        // Example ldd output:
        // libz.so.1 => /lib64/libz.so.1 (0x00007fca...)
        // /lib64/ld-linux-x86-64.so.2 (0x00007fca...)
        if (preg_match('/=>\s+([^\s]+)/', $line, $matches)) {
            $libs[] = $matches[1];
        } elseif (preg_match('/^\s*([^\s]+\.so[^\s]*)/', $line, $matches)) {
            $libs[] = $matches[1];
        }
    }

    return array_filter($libs, function($lib) {
        return file_exists($lib) && 
               (strpos($lib, '/lib64') === 0 || 
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
            // We need to process the dependency we just copied to find its own dependencies
            $toProcess[] = $targetPath;
        }
    }
}
