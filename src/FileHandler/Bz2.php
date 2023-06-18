<?php
/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace JakubBoucek\Tar\FileHandler;

use JakubBoucek\Tar\Exception\LogicException;
use JakubBoucek\Tar\Exception\RuntimeException;

class Bz2 implements FileHandler
{
    public static function match(string $filename): bool
    {
        return (bool)preg_match('/\.t?bz2?$/D', $filename);
    }

    /**
     * @inheritDoc
     */
    public function open(string $filename)
    {
        if (!self::isAvailable()) {
            throw new LogicException(
                __CLASS__ . " requires `ext-bz2` extension to open BZ2 compressed archive: '$filename'"
            );
        }

        $stream = bzopen($filename, 'r');

        if (is_resource($stream) === false) {
            throw new RuntimeException("Unable to open file '$filename'");
        }

        return $stream;
    }

    /**
     * @inheritDoc
     */
    public function close($stream): void
    {
        bzclose($stream);
    }

    public static function isAvailable(): bool
    {
        return function_exists('bzopen');
    }
}
