<?php

declare(strict_types=1);

namespace JakubBoucek\Tar\Parser;

use JakubBoucek\Tar\EntryType;
use JakubBoucek\Tar\Exception\InvalidArchiveFormatException;
use JakubBoucek\Tar\Exception\InvalidArgumentException;

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
class Header
{
    private const int NameFieldOffset = 0;
    private const int NameFieldLength = 100;
    private const int SizeFieldOffset = 124;
    private const int SizeFieldLength = 12;
    private const int TypeFieldOffset = 156;
    private const int MagicFieldOffset = 257;
    private const int MagicFieldLength = 6;

    private readonly EntryType $typeEnum;

    public function __construct(
        private readonly string $name,
        private readonly int $size,
        private readonly string $type,
    ) {
        if ($size < 0) {
            throw new InvalidArgumentException('Header size must be nonnegative.');
        }
        if (strlen($type) !== 1) {
            throw new InvalidArgumentException('Header type must be exactly one byte.');
        }
        $this->typeEnum = EntryType::tryFrom($type) ?? EntryType::Unknown;
    }

    /** Parse a physical TAR header, applying local PAX overrides only to ordinary entries. */
    public static function fromTarString(string $content, ?string $paxPath = null, ?int $paxSize = null): self
    {
        $length = strlen($content);
        if ($length !== 512) {
            throw new InvalidArchiveFormatException(
                sprintf('Tar header must be 512 bytes length, %d bytes got', $length),
            );
        }
        $magic = rtrim(substr($content, self::MagicFieldOffset, self::MagicFieldLength));
        if ($magic !== 'ustar') {
            throw new InvalidArchiveFormatException('Invalid TAR archive format: Invalid Tar header magic.');
        }
        $type = $content[self::TypeFieldOffset];
        if ((EntryType::tryFrom($type) ?? EntryType::Unknown)->isPax()) {
            $paxPath = null;
            $paxSize = null;
        }
        $name = $paxPath ?? rtrim(substr($content, self::NameFieldOffset, self::NameFieldLength), "\0");
        $size = $paxSize ?? self::parseSize(substr($content, self::SizeFieldOffset, self::SizeFieldLength));
        return new self($name, $size, $type);
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /** Preferred typed access; unrecognized flag bytes map to Unknown. */
    public function getTypeEnum(): EntryType
    {
        return $this->typeEnum;
    }

    public function isFile(): bool
    {
        return $this->typeEnum->isFile();
    }

    public function isDir(): bool
    {
        return $this->typeEnum === EntryType::Directory;
    }
}
