<?php

declare(strict_types=1);

namespace JakubBoucek\Tar;

use Iterator;
use IteratorAggregate;
use JakubBoucek\Tar\Exception\EofException;
use JakubBoucek\Tar\Exception\InvalidArchiveFormatException;
use JakubBoucek\Tar\Exception\InvalidArgumentException;
use JakubBoucek\Tar\Exception\RuntimeException;
use JakubBoucek\Tar\Parser\Header;
use JakubBoucek\Tar\Parser\LazyContent;
use JakubBoucek\Tar\Parser\Usage;

/**
 * @implements IteratorAggregate<File>
 */
class StreamReader implements IteratorAggregate
{
    /** @var resource */
    private $stream;

    /**
     * @param resource $stream Stream resource of TAR file
     */
    public function __construct($stream)
    {
        if (!is_resource($stream)) {
            throw new InvalidArgumentException('Stream must be a resource');
        }

        $this->stream = $stream;
    }

    /**
     * @return Iterator<int, File>
     */
    public function getIterator(): Iterator
    {
        while (!feof($this->stream)) {
            try {
                $header = $this->readHeader();
            } catch (EofException) {
                return;
            }

            $blockStart = ftell($this->stream);

            if (!$header->isValid()) {
                throw new InvalidArchiveFormatException(
                    sprintf(
                        'Invalid TAR archive format: Invalid Tar header format: at %s. bytes',
                        $blockStart
                    )
                );
            }

            $usage = new Usage();

            $contentSize = $header->getSize();
            $contentPadding = ($contentSize % 512) === 0 ? 0 : 512 - ($contentSize % 512);

            // Closure to lazy read, prevents backwards seek or repeated reads of discharged content
            /**
             * @param resource|null $target Resource to external stream to fill it by file content
             * @return resource
             */
            $contentClosure = function ($target = null) use ($usage, $contentSize, $contentPadding, $blockStart) {
                $usage->use();

                $isExternal = is_resource($target) && get_resource_type($target);
                $stream = $isExternal ? $target : fopen('php://temp', 'wb+');

                if(!$stream) {
                    throw new RuntimeException('Unable to create temporary stream.');
                }

                // Empty content means nothing to transport, nothing to seek
                if (!$contentSize) {
                    return $stream;
                }

                $bytes = stream_copy_to_stream($this->stream, $stream, $contentSize);

                if ($bytes !== $contentSize) {
                    throw new InvalidArchiveFormatException(
                        sprintf(
                            'Invalid TAR archive format: Unexpected end of file at position: %s, expected %d bytes, only %d bytes read',
                            $blockStart,
                            $contentSize,
                            ($bytes ?: 0)
                        )
                    );
                }

                // Only internal stream rewind
                if (!$isExternal) {
                    fseek($stream, 0);
                }

                if (!$contentPadding) {
                    return $stream;
                }

                // Skip padding
                $bytes = fseek($this->stream, $contentPadding, SEEK_CUR);

                if ($bytes === -1) {
                    throw new InvalidArchiveFormatException(
                        sprintf(
                            'Invalid TAR archive format: Unexpected end of file at position: %s, expected %d bytes of block padding',
                            $blockStart,
                            $contentPadding,
                        )
                    );
                }

                return $stream;
            };

            $content = new LazyContent($contentClosure);
            yield new File($header, $content);

            // Seek after unused content
            if (!$usage->used()) {
                $usage->use();
                $content->close();
                if ($contentSize) {
                    // Skip unused content
                    $bytes = fseek($this->stream, $contentSize + $contentPadding, SEEK_CUR);

                    if ($bytes === -1) {
                        throw new InvalidArchiveFormatException(
                            sprintf(
                                'Invalid TAR archive format: Unexpected end of file at position: %s, expected %d bytes of content and block padding',
                                $blockStart,
                                $contentSize + $contentPadding,
                            )
                        );
                    }
                }
            }
        }
    }

    private function readHeader(): Header
    {
        do {
            $header = fread($this->stream, 512);

            if ($header === '') {
                throw new EofException();
            }

            if ($header === false || strlen($header) < 512) {
                throw new InvalidArchiveFormatException(
                    sprintf(
                        'Invalid TAR archive format: Unexpected end of file, returned non-block size: %d bytes',
                        $header === false ? 0 : strlen($header)
                    )
                );
            }
        } while (self::isNullFilled($header));
        // ↑↑↑ TAR format inserts few blocks of nulls to EOF - just skip it

        return new Header($header);
    }

    private static function isNullFilled(string $string): bool
    {
        return trim($string, "\0") === '';
    }
}
