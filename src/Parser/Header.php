<?php

declare(strict_types=1);

namespace JakubBoucek\Tar\Parser;

use JakubBoucek\Tar\Exception\InvalidArchiveFormatException;
use JakubBoucek\Tar\Exception\InvalidArgumentException;

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
    private string $content;
    /** @var array<string, string|int> */
    private array $pax = [];

    public function __construct(string $content)
    {
        $length = strlen($content);
        if ($length !== 512) {
            throw new InvalidArgumentException(sprintf('Tar header must be 512 bytes length, %d bytes got', $length));
        }

        $this->content = $content;
    }

    public function harvestPaxData(string $paxData): void
    {
        foreach (explode("\n", $paxData) as $record) {
            if ($record === '') {
                continue;
            }
            $matchesFound = preg_match_all('/^(\d+) ([^=]+)=(.*)$/', $record, $matches);
            if (!$matchesFound) {
                throw new InvalidArchiveFormatException(
                    sprintf('Invalid Pax header record format: %s', $record)
                );
            }

            $key = $matches[2][0];
            $value = $matches[3][0];

            $this->pax[$key] = $value;
        }
    }

    public function isValid(): bool
    {
        return $this->getMagic() === 'ustar';
    }

    public function getName(): string
    {
        $str = substr($this->content, 0, 100);
        return rtrim($str, "\0");
    }

    public function getSize(): int
    {
        if (array_key_exists('size', $this->pax)) {
            return (int)$this->pax['size'];
        }

        $str = rtrim(substr($this->content, 124, 12));
        if (preg_match('/^[0-7]+$/D', $str) !== 1) {
            throw new InvalidArchiveFormatException(
                sprintf(
                    "Invalid Tar header format, file size must be octal number, '%s' got instead",
                    $str
                )
            );
        }

        return (int)octdec($str);
    }

    public function mergePaxHeader(Header $header): void
    {
        $this->pax = array_merge($header->pax, $this->pax);
    }

    /*
     * Values used in typeflag field
     * #define REGTYPE  '0'            // regular file
     * #define AREGTYPE '\0'           // regular file
     * #define LNKTYPE  '1'            // link
     * #define SYMTYPE  '2'            // reserved
     * #define CHRTYPE  '3'            // character special
     * #define BLKTYPE  '4'            // block special
     * #define DIRTYPE  '5'            // directory
     * #define FIFOTYPE '6'            // FIFO special
     * #define CONTTYPE '7'            // reserved
     * #define XHDTYPE  'x'            // Extended header referring to the next file in the archive
     * #define XGLTYPE  'g'            // Global extended header
     */

    public function getType(): string
    {
        return $this->content[156];
    }

    public function isFile(): bool
    {
        return $this->getType() === '0' || $this->getType() === "\0";
    }

    public function isDir(): bool
    {
        return $this->getType() === '5';
    }

    protected function getMagic(): string
    {
        return rtrim(substr($this->content, 257, 6));
    }
}
