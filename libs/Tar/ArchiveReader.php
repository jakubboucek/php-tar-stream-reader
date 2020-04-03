<?php

declare(strict_types=1);

namespace Tar;

use IteratorAggregate;
use RuntimeException;
use Tar\FileHandler\GzFileHandler;
use Tar\FileHandler\TarFileHandler;

/**
 * @implements IteratorAggregate<FileInfo>
 */
class ArchiveReader implements IteratorAggregate
{
    public const MODE_READ_FILES = true;
    public const MODE_SCAN_FILES = false;

    /** @var string */
    private $file;

    /** @var bool */
    private $mode = true;

    public function __construct(string $file, bool $mode = self::MODE_READ_FILES)
    {
        if (is_readable($file) === false) {
            throw new RuntimeException("Unable to read file \'{$file}\'");
        }

        $this->file = $file;
        $this->mode = $mode;
    }
    public function getIterator(): ArchiveIterator
    {
        $handler = $this->isGzipped($this->file) ? new GzFileHandler() : new TarFileHandler();

        return new ArchiveIterator($this->file, $handler, $this->mode);
    }

    protected function isGzipped(string $file): bool
    {
        return preg_match('/\.t?gz$/D', $file) === 1;
    }
}
