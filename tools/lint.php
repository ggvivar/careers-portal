<?php
$root = dirname(__DIR__);
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/app'));
$failed = false;
foreach ($iterator as $file) {
    if (! $file->isFile() || strtolower($file->getExtension()) !== 'php') continue;
    $cmd = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file->getPathname());
    exec($cmd, $out, $code);
    if ($code !== 0) { echo implode(PHP_EOL, $out) . PHP_EOL; $failed = true; }
    $out = [];
}
exit($failed ? 1 : 0);
