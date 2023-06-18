<?php

declare(strict_types=1);

namespace JakubBoucek\Tar;

use Iterator;
use IteratorAggregate;
use JakubBoucek\Tar\FileHandler;

/**
 * @implements IteratorAggregate<File>
 */
class FileReader implements IteratorAggregate
{
    private string $filename;
    private FileHandler\FileHandler $handler;

    public function __construct(string $filename, ?FileHandler\FileHandler $handler = null)
    {
        $this->filename = $filename;

        if (!$handler) {
            $handler = match (true) {
                FileHandler\Gzip::match($filename) => new FileHandler\Gzip(),
                FileHandler\Bz2::match($filename) => new FileHandler\Bz2(),
                default => new FileHandler\Plain(),
            };
        }

        $this->handler = $handler;
    }

    /**
     * @return Iterator<File>
     */
    public function getIterator(): Iterator
    {
        $stream = $this->handler->open($this->filename);
        yield from new StreamReader($stream);
        $this->handler->close($stream);
    }
}
