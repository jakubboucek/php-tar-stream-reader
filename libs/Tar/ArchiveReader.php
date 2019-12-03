<?php
declare(strict_types=1);

namespace Tar;

use IteratorAggregate;
use RuntimeException;
use Tar\FileHandler\GzFileHandler;
use Tar\FileHandler\TarFileHandler;

class ArchiveReader implements IteratorAggregate
{
    /** @var string */
    private string $file;

    public function __construct(string $file)
    {
        if (is_readable($file) === false) {
            throw new RuntimeException("Unable to read file \'{$file}\'");
        }

        $this->file = $file;
    }

    public function getIterator(): ArchiveIterator
    {
        $handler = $this->isGzipped($this->file)?new GzFileHandler():new TarFileHandler();

        return new ArchiveIterator($this->file, $handler);
    }

    protected function isGzipped(string $file): bool
    {
        return preg_match('/\.t?gz$/D', $file) === 1;
    }
}
