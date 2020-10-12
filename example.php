<?php

declare(strict_types=1);

use JakubBoucek\Tar\ArchiveReader;

require __DIR__ . '/vendor/autoload.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(400);
    echo 'ERROR: Tool is callable only from command-line' . PHP_EOL;
    die(1);
}

// Parse & validate command arguments
if ($argc < 2) {
    throw new RuntimeException(sprintf('Usage: %s <file>', basename(__FILE__)));
}

$archiveFile = $argv[1];

$reader = ArchiveReader::scan($archiveFile);


echo sprintf("%10s\t%10s\t%4s\t%s\n", 'File size', 'Memory', 'Type', 'File name');
foreach ($reader as $filename => $file) {
    echo sprintf(
        "%10d\t%10d\t%4s\t%s\n",
        $file->getSize(),
        memory_get_usage(),
        $file->isDir() ? 'dir' : 'file',
        $filename
    );

    // You can also read content of file:
    //$file->getContent();
}
