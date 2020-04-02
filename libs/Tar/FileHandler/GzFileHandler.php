<?php

declare(strict_types=1);

namespace Tar\FileHandler;

use RuntimeException;

class GzFileHandler implements IHandler
{

    /**
     * @inheritDoc
     */
    public function open(string $file)
    {
        $handle = gzopen($file, 'rb', 0);

        if (is_resource($handle) === false) {
            throw new RuntimeException("Unable to open file \'{$file}\'");
        }

        return $handle;
    }

    /**
     * @inheritDoc
     */
    public function close($handler): void
    {
        gzclose($handler);
    }
}
