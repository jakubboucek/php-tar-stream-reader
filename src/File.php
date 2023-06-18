<?php

declare(strict_types=1);

namespace JakubBoucek\Tar;

use JakubBoucek\Tar\Parser\Header;
use Psr\Http\Message\StreamInterface;

class File
{
    private Header $header;
    private StreamInterface $content;

    /**
     * @param Header $header
     * @param StreamInterface $content
     */
    public function __construct(Header $header, StreamInterface $content)
    {
        $this->header = $header;
        $this->content = $content;
    }

    public function __toString(): string
    {
        return $this->getName();
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

    public function getContent(): StreamInterface
    {
        return $this->content;
    }
}
