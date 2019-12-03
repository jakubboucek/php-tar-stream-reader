<?php
declare(strict_types=1);

namespace Tar;

use IteratorAggregate;
use RuntimeException;

class FileReader implements IteratorAggregate
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

    public function getIterator(): FileIterator
    {
        return new FileIterator($this->file);
    }
}
