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

class Header
{
    private const int NameFieldOffset = 0;
    private const int NameFieldLength = 100;

    private const int SizeFieldOffset = 124;
    private const int SizeFieldLength = 12;

    private const int TypeFieldOffset = 156;

    private const int MagicFieldOffset = 257;
    private const int MagicFieldLength = 6;

    public function __construct(
        private readonly string $content,
        private readonly ?string $paxPath = null,
        private readonly ?int $paxSize = null,
    ) {
        $length = strlen($content);
        if ($length !== 512) {
            throw new InvalidArgumentException(sprintf('Tar header must be 512 bytes length, %d bytes got', $length));
        }
    }

    /** @internal */
    public function withPax(?string $path, ?int $size): self
    {
        return new self($this->content, $path, $size);
    }

    public function isValid(): bool
    {
        return $this->getMagic() === 'ustar';
    }

    public function getName(): string
    {
        if ($this->paxPath !== null) {
            return $this->paxPath;
        }

        $str = substr($this->content, self::NameFieldOffset, self::NameFieldLength);
        return rtrim($str, "\0");
    }

    public function getSize(): int
    {
        if ($this->paxSize !== null) {
            return $this->paxSize;
        }

        $str = rtrim(substr($this->content, self::SizeFieldOffset, self::SizeFieldLength));
        if (preg_match('/^[0-7]+$/D', $str) !== 1) {
            throw new InvalidArchiveFormatException(
                sprintf(
                    "Invalid Tar header format, file size must be octal number, '%s' got instead",
                    $str,
                ),
            );
        }

        return (int) octdec($str);
    }

    public function getType(): string
    {
        return $this->content[self::TypeFieldOffset];
    }

    /** Preferred typed access; unknown flags return null, and legacy NUL means RegularFile. */
    public function getTypeEnum(): ?EntryType
    {
        $type = $this->getType();
        return $type === "\0" ? EntryType::RegularFile : EntryType::tryFrom($type);
    }

    public function isFile(): bool
    {
        return $this->getTypeEnum() === EntryType::RegularFile;
    }

    public function isDir(): bool
    {
        return $this->getTypeEnum() === EntryType::Directory;
    }

    protected function getMagic(): string
    {
        return rtrim(substr($this->content, self::MagicFieldOffset, self::MagicFieldLength));
    }
}
