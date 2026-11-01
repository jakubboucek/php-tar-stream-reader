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
use JakubBoucek\Tar\Parser\TarHeaderParser;
use JakubBoucek\Tar\Parser\LazyContent;
use JakubBoucek\Tar\Parser\PaxHeader;
use JakubBoucek\Tar\Exception\NotImplementedException;
use JakubBoucek\Tar\Parser\Usage;

use function is_resource;
use function sprintf;
use function strlen;

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
                $header = $this->readEntryHeader();
            } catch (EofException) {
                return;
            }

            $blockStart = ftell($this->stream);

            $usage = new Usage();

            $contentSize = $header->size;
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

                if (!$stream) {
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
                            'Invalid TAR archive format: Unexpected end of file at position: '
                            . '%s, expected %d bytes, only %d bytes read',
                            $blockStart,
                            $contentSize,
                            ($bytes ?: 0),
                        ),
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
                $this->discardBytes($contentPadding);

                return $stream;
            };

            $content = new LazyContent($contentClosure);
            try {
                yield new File($header, $content);
            } finally {
                // Unread content cannot outlive its archive position or generator.
                if (!$usage->used()) {
                    $content->close();
                }
            }

            // Discard unused content
            if (!$usage->used()) {
                $usage->use();
                if ($contentSize) {
                    // Skip unused content
                    $this->discardBytes($contentSize);
                    $this->discardBytes($contentPadding);
                }
            }
        }
    }

    /**
     * Reads and discards unused content or padding in chunks of at most 8 KiB.
     * Unlike seeking, reading verifies that the skipped bytes actually exist,
     * while keeping memory usage bounded regardless of the requested length.
     */
    private function discardBytes(int $length): void
    {
        $start = ftell($this->stream);
        $remaining = $length;
        while ($remaining > 0) {
            $chunk = fread($this->stream, min(8192, $remaining));
            if ($chunk === false || $chunk === '') {
                throw new InvalidArchiveFormatException(
                    sprintf(
                        'Invalid TAR archive format: Unexpected end of file at position: '
                        . '%s, expected %d bytes to discard, only %d bytes read',
                        $start,
                        $length,
                        $length - $remaining,
                    ),
                );
            }
            $remaining -= strlen($chunk);
        }
    }

    private function readEntryHeader(): Header
    {
        $metadata = [];
        $pendingLocal = false;
        while (true) {
            try {
                $rawHeader = $this->readHeader($pendingLocal);
            } catch (EofException $exception) {
                if ($pendingLocal) {
                    throw new InvalidArchiveFormatException('Missing file after local PAX header.', 0, $exception);
                }
                throw $exception;
            }
            $header = TarHeaderParser::parse($rawHeader, $metadata);

            if (!$header->type->isPax()) {
                return $header;
            }

            // PAX processing

            $size = $header->size;
            if ($size > PaxHeader::MaxPayloadSize) {
                throw new NotImplementedException('PAX payloads larger than 1 MiB are not supported.');
            }
            $payload = '';
            $remaining = $size;
            while ($remaining > 0) {
                $chunk = fread($this->stream, min(8192, $remaining));
                if ($chunk === false || $chunk === '') {
                    throw new InvalidArchiveFormatException('Unexpected end of PAX payload.');
                }
                $payload .= $chunk;
                $remaining -= strlen($chunk);
            }
            $values = PaxHeader::parse($payload, $header->type === EntryType::PaxGlobalHeader);
            $this->discardBytes((512 - ($size % 512)) % 512);
            if ($header->type === EntryType::PaxExtendedHeader) {
                $metadata = array_replace($metadata, $values);
                $pendingLocal = true;
            }
        }
    }

    private function readHeader(bool $pendingLocal = false): string
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
                        $header === false ? 0 : strlen($header),
                    ),
                );
            }
            if ($pendingLocal && self::isNullFilled($header)) {
                throw new InvalidArchiveFormatException(
                    'Missing file after local PAX header.',
                );
            }
        } while (self::isNullFilled($header));
        // ↑↑↑ TAR format inserts few blocks of nulls to EOF - just skip it

        return $header;
    }

    private static function isNullFilled(string $string): bool
    {
        return trim($string, "\0") === '';
    }
}
