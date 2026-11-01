<?php

declare(strict_types=1);

namespace JakubBoucek\Tar;

use Iterator;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<File>
 */
class FileReader implements IteratorAggregate
{
    private FileHandler\FileHandler $handler;

    public function __construct(
        private readonly string $filename,
        ?FileHandler\FileHandler $handler = null,
    ) {
        $this->handler = $handler ?? match (true) {
            FileHandler\Gzip::match($filename) => new FileHandler\Gzip(),
            FileHandler\Bz2::match($filename) => new FileHandler\Bz2(),
            default => new FileHandler\Plain(),
        };
    }

    /**
     * @return Iterator<File>
     */
    public function getIterator(): Iterator
    {
        $stream = $this->handler->open($this->filename);
        try {
            yield from new StreamReader($stream);
        } finally {
            $this->handler->close($stream);
        }
    }
}
