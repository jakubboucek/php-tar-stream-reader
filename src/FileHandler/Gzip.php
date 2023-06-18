<?php
/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace JakubBoucek\Tar\FileHandler;

use JakubBoucek\Tar\Exception\LogicException;
use JakubBoucek\Tar\Exception\RuntimeException;

class Gzip implements FileHandler
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
        if (!self::isAvailable()) {
            throw new LogicException(
                __CLASS__ . " requires `ext-zlib` extension to open GZipped archive: '$filename'"
            );
        }

        $handle = gzopen($filename, 'rb');

        if (is_resource($handle) === false) {
            throw new RuntimeException("Unable to open file '$filename'");
        }

        return $handle;
    }

    /**
     * @inheritDoc
     */
    public function close($stream): void
    {
        gzclose($stream);
    }

    public static function isAvailable(): bool
    {
        return function_exists('gzopen');
    }
}
