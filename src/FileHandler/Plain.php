<?php

declare(strict_types=1);

namespace JakubBoucek\Tar\FileHandler;

use RuntimeException;

class Plain implements FileHandler
{
    /**
     * @inheritDoc
     */
    public function open(string $filename)
    {
        $handle = fopen($filename, 'rb');

        if (is_resource($handle) === false) {
            throw new RuntimeException("Unable to open file \'$filename\'");
        }

        return $handle;
    }

    /**
     * @inheritDoc
     */
    public function close($stream): void
    {
        fclose($stream);
    }
}
