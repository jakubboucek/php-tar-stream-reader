<?php

declare(strict_types=1);

$files = [];

for ($i = 0; $i <= 1024; $i++) {
    $data = $i === 0 ? '' : random_bytes($i);
    $hash = md5($data);
    $file = sprintf('%04s-bytes.bin', $i);
    file_put_contents(__DIR__ . '/' . $file, $data);
    $files[] = [
        'file' => $file,
        'length' => $i,
        'hash' => $hash,
    ];
}

file_put_contents(
    __DIR__ . '/test-archive-random.json',
    json_encode($files, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
);

$list = implode(' ', array_map(fn($file) => $file['file'], $files));

exec("tar -cvzf test-archive-random.tgz $list");
exec("rm $list");
