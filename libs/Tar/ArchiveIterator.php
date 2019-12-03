<?php
declare(strict_types=1);

namespace Tar;

use Iterator;
use RuntimeException;
use Tar\FileHandler\IHandler;

class ArchiveIterator implements Iterator
{
    /** @var resource */
    private $handle;

    private ?FileInfo $currentFile;

    private IHandler $fileHandler;

    public function __construct(string $file, IHandler $fileHandler)
    {

        $handle = $fileHandler->open($file);

        if (is_resource($handle) === false) {
            throw new RuntimeException("Unable to open file \'{$file}\'");
        }

        $this->handle = $handle;
        $this->fileHandler = $fileHandler;
    }

    public function __destruct()
    {
        $this->fileHandler->close($this->handle);
    }


    public function rewind(): void
    {
        fseek($this->handle, 0);
        $this->next();
    }

    public function next(): void
    {
        $this->currentFile = null;

        if (feof($this->handle)) {
            return;
        }

        $position = ftell($this->handle);

        $headerData = fread($this->handle, 512);
        if (strlen($headerData) < 512) {
            trigger_error(sprintf('Unexpected end of file, returned non-block size: %d bytes', strlen($headerData)));
            return;
        }

        $header = new Header($headerData);
        unset($headerData);
        if ($header->isValid() === false) {
            // Tar format insert few blocks of nulls to EOF - check if already nulls or corrupted
            if ($header->isNullFilled() === false) {
                throw new RuntimeException("Invalid data Tar header format in block position: $position bytes");
            }
            return;
        }

        $contentSize = $header->getSize();
        $contentBlockSize = $contentSize + (($contentSize % 512) === 0 ? 0 : 512 - ($contentSize % 512));

        $content = '';
        if ($contentBlockSize > 0) {
            $blockContent = fread($this->handle, $contentBlockSize);
            if (strlen($blockContent) < $contentBlockSize) {
                throw new RuntimeException(sprintf(
                    'Unexpected end of file, returned non-block size: %d bytes',
                    strlen($blockContent)
                ));
            }
            $content = substr($blockContent, 0, $contentSize);
            unset($blockContent);
        }

        $this->currentFile = new FileInfo($header, $content);
    }

    public function valid(): bool
    {
        return $this->currentFile !== null;
    }

    public function current(): ?FileInfo
    {
        return $this->currentFile;
    }

    public function key(): ?string
    {
        return $this->valid() ? $this->currentFile->getName() : null;
    }
}
