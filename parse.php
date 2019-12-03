<?php
declare(strict_types=1);

use Tar\FileReader;

require __DIR__.'/vendor/autoload.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(400);
    echo 'ERROR: Tool is callable only from command-line' . PHP_EOL;
    die(1);
}

// Parse & validate command arguments
if ($argc < 2) {
    throw new \RuntimeException(sprintf('Usage: %s <file>', basename(__FILE__)));
}


//Download ARES archive from http://wwwinfo.mfcr.cz/ares/ares_opendata.html.cz
//$archiveFile = __DIR__ . '/source/ares_vreo_all.tar.gz';
$archiveFile = $argv[1];

$reader = new FileReader($archiveFile);

foreach ($reader as $file) {
    echo $file->getSize() . "\t" . $file->getName() . PHP_EOL;
    //$file->getContent();
}

