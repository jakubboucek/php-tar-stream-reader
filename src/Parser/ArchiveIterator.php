<?php

declare(strict_types=1);

namespace JakubBoucek\Tar\Parser;

use Iterator;
use JakubBoucek\Tar\Exception\InvalidArchiveFormatException;
use JakubBoucek\Tar\FileHandler\IHandler;
use JakubBoucek\Tar\FileInfo;

/**
 * @implements Iterator<string, FileInfo>
 */
class ArchiveIterator implements Iterator
{
    /** @var resource */
    private $handle;

    /** @var FileInfo|null */
    private $currentFile;

    /** @var IHandler */
    private $fileHandler;

    /** @var bool */
    private $readContent;

    public function __construct(string $file, IHandler $fileHandler, bool $readContent = true)
    {
        $handle = $fileHandler->open($file);

        $this->handle = $handle;
        $this->fileHandler = $fileHandler;
        $this->readContent = $readContent;
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
        if ($headerData === false || strlen($headerData) < 512) {
            throw new InvalidArchiveFormatException(
                sprintf(
                    'Invalid TAR archive format: Unexpected end of file, returned non-block size: %d bytes',
                    $headerData === false ? 0 : strlen($headerData)
                )
            );
        }

        $header = new Header($headerData);

        unset($headerData);
        if ($header->isValid() === false) {
            // TAR format insert few blocks of nulls to EOF - check if already nulls or corrupted
            if ($header->isNullFilled() === false) {
                throw new InvalidArchiveFormatException(
                    sprintf(
                        'Invalid TAR archive format: Invalid data Tar header format in block position: %s bytes',
                        $position
                    )
                );
            }

            // Sometime Archive contains null-filled block instad of header
            return;
        }

        $contentSize = $header->getSize();
        $contentBlockSize = $contentSize + (($contentSize % 512) === 0 ? 0 : 512 - ($contentSize % 512));

        $content = '';
        if ($contentBlockSize > 0) {
            if ($this->readContent) {
                $blockContent = fread($this->handle, $contentBlockSize);
                if ($blockContent === false || strlen($blockContent) < $contentBlockSize) {
                    throw new InvalidArchiveFormatException(
                        sprintf(
                            'Invalid TAR archive format: Unexpected end of file, returned non-block size: %d bytes',
                            $blockContent === false ? 0 : strlen($blockContent)
                        )
                    );
                }
                $content = substr($blockContent, 0, $contentSize);
                unset($blockContent);
            } else {
                $content = null;
                fseek($this->handle, $contentBlockSize, SEEK_CUR);
            }
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
