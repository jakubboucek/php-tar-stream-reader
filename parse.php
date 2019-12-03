<?php
declare(strict_types=1);

use Tar\FileReader;

require __DIR__.'/vendor/autoload.php';

//Download ARES archive from http://wwwinfo.mfcr.cz/ares/ares_opendata.html.cz
$archiveFile = __DIR__ . '/source/ares_vreo_all.tar.gz';

$reader = new FileReader($archiveFile);

foreach ($reader as $file) {
    echo $file->getSize() . "\t" . $file->getName() . PHP_EOL;
    //$file->getContent();
}

