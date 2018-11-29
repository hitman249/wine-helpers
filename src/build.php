<?php

$files = include __DIR__ . '/files.php';

$additional = [];

foreach ($files['additional'] as $path) {
    $file = explode('<?php', file_get_contents(__DIR__ . "/{$path}"));

    if (!$file[0]) {
        unset($file[0]);
    }

    $additional[] = implode('<?php', $file);
}

$global = [];

foreach ($files['global'] as $path) {
    $file = explode('<?php', file_get_contents(__DIR__ . "/{$path}"));

    if (!$file[0]) {
        unset($file[0]);
    }

    $global[] = implode('<?php', $file);
}

file_put_contents(
//    dirname(__DIR__). '/start2',
    '/home/neiron/PhpstormProjects/test/start2',
    file_get_contents(__DIR__ . '/loader.sh') . "\n\n<?php\n" . implode("\n\n", $additional) . "\n\nnamespace { " . implode("\n\n", $global) . "\n}"
);