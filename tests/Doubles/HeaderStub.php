<?php

declare(strict_types=1);

namespace JakubBoucek\Tar\Tests\Doubles;

use JakubBoucek\Tar\Parser\Header;

/** Metadata-only double with independent classification flags to verify File delegation. */
class HeaderStub extends Header
{
    public bool $regular = true;
    public bool $directory = true;

    public function isFile(): bool
    {
        return $this->regular;
    }

    public function isDir(): bool
    {
        return $this->directory;
    }
}
