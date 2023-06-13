<?php

declare(strict_types=1);

use JakubBoucek\Tar\FileReader;

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

foreach (new FileReader(__DIR__ . '/tests/assets/test-archive-random.tgz') as $file) {
    echo sprintf(
        "%20s: announced %6s bytes, really %6s bytes, hash: %s\n",
        $file->getName(),
        $file->getSize(),
        strlen((string)$file->getContent()),
        md5((string)$file->getContent())
    );
}
