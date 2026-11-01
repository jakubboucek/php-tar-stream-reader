<?php

declare(strict_types=1);

namespace JakubBoucek\Tar\Parser;

use JakubBoucek\Tar\EntryType;
use JakubBoucek\Tar\Exception\InvalidArgumentException;

/** @internal */
readonly class Header
{
    public function __construct(
        public string $name,
        public int $size,
        public EntryType $type,
    ) {
        if ($size < 0) {
            throw new InvalidArgumentException('Header size must be nonnegative.');
        }
    }

    public static function fromTarString(string $content): self
    {
        return TarHeaderParser::parse($content);
    }
}
