<?php

declare(strict_types=1);

namespace JakubBoucek\Tar\Tests\Doubles;

use JakubBoucek\Tar\Parser\Header;

/** Metadata-only double; intentionally bypasses binary header parsing. */
class HeaderStub extends Header
{
    public bool $regular = true;
    public bool $directory = true;

    public function __construct(
        private string $name,
        private int $size,
        private string $type,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function isFile(): bool
    {
        return $this->regular;
    }

    public function isDir(): bool
    {
        return $this->directory;
    }
}
