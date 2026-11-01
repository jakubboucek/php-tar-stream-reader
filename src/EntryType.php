<?php

declare(strict_types=1);

namespace JakubBoucek\Tar;

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

/** Recognized TAR type flags; recognizing a flag does not imply format support. */
enum EntryType: string
{
    case RegularFile = '0';
    case ARegularFile = "\0";
    case HardLink = '1';
    case SymbolicLink = '2';
    case CharacterDevice = '3';
    case BlockDevice = '4';
    case Directory = '5';
    case Fifo = '6';
    case ContiguousFile = '7';
    case PaxExtendedHeader = 'x';
    case PaxGlobalHeader = 'g';

    public function isFile(): bool
    {
        return $this === self::RegularFile || $this === self::ARegularFile;
    }

    public function isDir(): bool
    {
        return $this === self::Directory;
    }

    public function isPax(): bool
    {
        return $this === self::PaxExtendedHeader || $this === self::PaxGlobalHeader;
    }
}
