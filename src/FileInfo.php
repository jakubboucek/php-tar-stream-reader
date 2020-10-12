<?php

declare(strict_types=1);

namespace JakubBoucek\Tar;

use JakubBoucek\Tar\Exception\LogicException;
use JakubBoucek\Tar\Parser\Header;

class FileInfo
{
    /** @var Header */
    private $header;

    /** @var string|null */
    private $content;

    public function __construct(Header $header, ?string $content)
    {
        $this->content = $content;
        $this->header = $header;
    }

    public function getName(): string
    {
        return $this->header->getName();
    }

    public function getType(): string
    {
        return $this->header->getType();
    }

    public function isFile(): bool
    {
        return $this->header->isFile();
    }

    public function isDir(): bool
    {
        return $this->header->isDir();
    }

    public function getSize(): int
    {
        return $this->header->getSize();
    }

    public function getContent(): string
    {
        if ($this->content === null) {
            throw new LogicException('Unable to read file content in scan-only mode');
        }
        return $this->content;
    }
}
