<?php

declare(strict_types=1);

namespace JakubBoucek\Tar;

/** Recognized TAR type flags; recognizing a flag does not imply format support. */
enum EntryType: string
{
    case RegularFile = '0';
    case HardLink = '1';
    case SymbolicLink = '2';
    case CharacterDevice = '3';
    case BlockDevice = '4';
    case Directory = '5';
    case Fifo = '6';
    case ContiguousFile = '7';
    case PaxExtendedHeader = 'x';
    case PaxGlobalHeader = 'g';
}
