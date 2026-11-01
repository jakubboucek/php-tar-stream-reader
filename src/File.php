<?php

declare(strict_types=1);

namespace JakubBoucek\Tar;

use JakubBoucek\Tar\Parser\LightStreamInterface;
use JakubBoucek\Tar\Parser\Header;
use Stringable;

readonly class File implements Stringable
{
    public function __construct(
        private Header $header,
        private LightStreamInterface $content,
    ) {}

    public function __toString(): string
    {
        return $this->getName();
    }


    public function getName(): string
    {
        return $this->header->name;
    }

    public function getType(): string
    {
        return $this->header->type->value;
    }

    public function getTypeEnum(): EntryType
    {
        return $this->header->type;
    }

    public function isFile(): bool
    {
        return $this->header->type->isFile();
    }

    public function isDir(): bool
    {
        return $this->header->type->isDir();
    }

    public function getSize(): int
    {
        return $this->header->size;
    }

    public function getContent(): LightStreamInterface
    {
        return $this->content;
    }
}
