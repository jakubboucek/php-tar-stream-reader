<?php

declare(strict_types=1);

namespace JakubBoucek\Tar\FileHandler;

use RuntimeException;

class TarFileHandler implements FileHandler
{
    public static function match(string $filename): bool
    {
        return (bool)preg_match('/\.t?gz$/D', $filename);
    }

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
