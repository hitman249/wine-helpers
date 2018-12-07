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

$data = file_get_contents(__DIR__ . '/loader.sh') . "\n\n<?php\n" . implode("\n\n", $additional) . "\n\nnamespace { " . implode("\n\n", $global) . "\n}";

file_put_contents(dirname(__DIR__). '/start', $data);
file_put_contents('/home/neiron/PhpstormProjects/test/start', $data);
