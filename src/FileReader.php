<?php

declare(strict_types=1);

namespace JakubBoucek\Tar;

use Iterator;
use IteratorAggregate;
use JakubBoucek\Tar\FileHandler\Bz2FileHandler;
use JakubBoucek\Tar\FileHandler\FileHandler;
use JakubBoucek\Tar\FileHandler\GzFileHandler;
use JakubBoucek\Tar\FileHandler\TarFileHandler;
use JakubBoucek\Tar\Parser\File;

/**
 * @implements IteratorAggregate<File>
 */
class FileReader implements IteratorAggregate
{
    private string $filename;
    private FileHandler $handler;

    public function __construct(string $filename, ?FileHandler $handler = null)
    {
        $this->filename = $filename;

        if (!$handler) {
            $handler = match (true) {
                GzFileHandler::match($filename) => new GzFileHandler(),
                Bz2FileHandler::match($filename) => new Bz2FileHandler(),
                default => new TarFileHandler(),
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
