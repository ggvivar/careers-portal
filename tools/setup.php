<?php
$root = dirname(__DIR__);
$directories = [
    'writable/cache', 'writable/logs', 'writable/session',
    'writable/debugbar', 'writable/uploads', 'writable/careers-storage',
];
foreach ($directories as $relative) {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    if (! is_dir($path) && ! mkdir($path, 0775, true) && ! is_dir($path)) {
        fwrite(STDERR, "Unable to create: {$path}\n");
        exit(1);
    }
    if (! is_file($path . DIRECTORY_SEPARATOR . '.gitkeep')) {
        touch($path . DIRECTORY_SEPARATOR . '.gitkeep');
    }
    echo "Ready: {$relative}\n";
}
if (! is_file($root . DIRECTORY_SEPARATOR . '.env')) {
    echo "\n.env is missing. Copy .env.xampp.example or .env.example to .env and configure it.\n";
}
