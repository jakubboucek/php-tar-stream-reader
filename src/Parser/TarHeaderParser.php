<?php

declare(strict_types=1);

namespace JakubBoucek\Tar\Parser;

use JakubBoucek\Tar\EntryType;
use JakubBoucek\Tar\Exception\InvalidArchiveFormatException;
use ValueError;

use function sprintf;
use function strlen;

/*
    TAR HEADER FORMAT
    POSIX header

    struct posix_header
    {                             // byte offset
         char name[100];          //   0
         char mode[8];            // 100
         char uid[8];             // 108
         char gid[8];             // 116
         char size[12];           // 124
         char mtime[12];          // 136
         char chksum[8];          // 148
         char typeflag;           // 156
         char linkname[100];      // 157
         char magic[6];           // 257
         char version[2];         // 263
         char uname[32];          // 265
         char gname[32];          // 297
         char devmajor[8];        // 329
         char devminor[8];        // 337
         char prefix[155];        // 345
                                  // 500
    };
*/

/** @internal */
class TarHeaderParser
{
    private const int NameFieldOffset = 0;
    private const int NameFieldLength = 100;
    private const int SizeFieldOffset = 124;
    private const int SizeFieldLength = 12;
    private const int TypeFieldOffset = 156;
    private const int MagicFieldOffset = 257;
    private const int MagicFieldLength = 6;

    /** @param array{path?: string, size?: int} $pax */
    public static function parse(string $content, array $pax = []): Header
    {
        $length = strlen($content);
        if ($length !== 512) {
            throw new InvalidArchiveFormatException(
                sprintf('Tar header must be 512 bytes length, %d bytes got', $length),
            );
        }

        // Validate the physical header before applying any metadata overrides.
        $magic = rtrim(substr($content, self::MagicFieldOffset, self::MagicFieldLength));
        if ($magic !== 'ustar') {
            throw new InvalidArchiveFormatException('Invalid TAR archive format: Invalid Tar header magic.');
        }

        $typeVal = $content[self::TypeFieldOffset];
        try {
            $type = EntryType::from($typeVal);
        } catch (ValueError $e) {
            throw new InvalidArchiveFormatException(
                sprintf("Invalid Tar header format, file type is unknown: '%s'.", $typeVal),
                previous: $e,
            );
        }

        if ($type->isPax()) {
            $pax = [];
        }
        $name = $pax['path'] ?? rtrim(substr($content, self::NameFieldOffset, self::NameFieldLength), "\0");
        $size = $pax['size'] ?? self::parseSize(substr($content, self::SizeFieldOffset, self::SizeFieldLength));
        return new Header($name, $size, $type);
    }

    private static function parseSize(string $field): int
    {
        $digits = rtrim($field);
        if (preg_match('/^[0-7]+$/D', $digits) !== 1) {
            throw new InvalidArchiveFormatException(
                sprintf("Invalid Tar header format, file size must be octal number, '%s' got instead", $digits),
            );
        }
        $size = octdec($digits);
        if ($size > PHP_INT_MAX) {
            throw new InvalidArchiveFormatException('Tar header size exceeds PHP_INT_MAX.');
        }
        return (int) $size;
    }
}
