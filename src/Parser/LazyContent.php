<?php

declare(strict_types=1);

namespace JakubBoucek\Tar\Parser;

use Closure;
use Exception;
use JakubBoucek\Tar\Exception\FileContentClosedException;
use JakubBoucek\Tar\Exception\InvalidArgumentException;
use JakubBoucek\Tar\Exception\RuntimeException;

class LazyContent implements LightStreamInterface
{
    private ?Closure $contentClosure;
    /** @var resource|null */
    private $stream;

    public function __construct(Closure $contentClosure)
    {
        $this->contentClosure = $contentClosure;
    }

    public function __destruct()
    {
        $this->close();
    }

    public function __toString(): string
    {
        if ($this->isSeekable()) {
            $this->seek(0);
        }

        return $this->getContents();
    }

    /**
     * @param resource $stream
     * @return void
     */
    public function toStream($stream): void
    {
        if ($this->isClosed()) {
            throw new FileContentClosedException(
                "File's Content is already closed, try to fetch it right after File fetched from Reader."
            );
        }

        if (!is_resource($stream)) {
            throw new InvalidArgumentException('Stream must be a resource');
        }

        // Direct stream-clean way, memory humble way
        if (!$this->isLoaded()) {
            // Call closure to fill stream
            ($this->contentClosure)($stream);
            $this->contentClosure = null;
            return;
        }

        // Backup way - reuse already loaded stream
        $result = stream_copy_to_stream($this->getStream(), $stream);

        if ($result === false) {
            throw new RuntimeException('Unable to write to stream');
        }

        // Close to prevent reuse content only writed to external stream
        $this->close();
    }

    /**
     * @param resource|null $context Stream context (e.g. from `stream_context_create()`)
     */
    public function toFile(string $file, $context = null): void
    {
        $stream = fopen($file, 'wb', false, $context);
        if (!is_resource($stream)) {
            throw new InvalidArgumentException("Unable to open file: '$file'");
        }

        $this->toStream($stream);
        fclose($stream);
    }

    public function isLoaded(): bool
    {
        return isset($this->stream);
    }

    public function isClosed(): bool
    {
        return !isset($this->stream) && !isset($this->contentClosure);
    }

    public function close(): void
    {
        if ($this->isClosed()) {
            return;
        }
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }

        $this->stream = $this->contentClosure = null;
    }


    /**
     * @return resource
     */
    public function detach()
    {
        $stream = $this->getStream();
        if ($this->isSeekable()) {
            $this->seek(0);
        }
        $this->stream = $this->contentClosure = null;
        return $stream;
    }

    public function getSize(): ?int
    {
        trigger_error(sprintf("Method '%s' not implemented.", __METHOD__), E_USER_WARNING);
        return null;
    }

    public function tell(): int
    {
        $result = ftell($this->getStream());

        if ($result === false) {
            throw new RuntimeException('Unable to determine stream position');
        }

        return $result;
    }

    public function eof(): bool
    {
        return feof($this->getStream());
    }

    public function isSeekable(): bool
    {
        return (bool)stream_get_meta_data($this->getStream())['seekable'];
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (!$this->isSeekable()) {
            throw new RuntimeException('Stream is not seekable');
        }
        if (fseek($this->getStream(), $offset, $whence) === -1) {
            throw new RuntimeException(
                sprintf(
                    "Unable to seek to stream position %d with whence %s",
                    $offset,
                    var_export($whence, true)
                )
            );
        }
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write(string $string): int
    {
        throw new RuntimeException('Cannot write to a non-writable stream');
    }

    public function isReadable(): bool
    {
        $this->getStream();
        return true;
    }

    public function read(int $length): string
    {
        if ($length < 0) {
            throw new RuntimeException('Length parameter cannot be negative');
        }

        if (0 === $length) {
            return '';
        }

        try {
            $string = fread($this->getStream(), $length);
        } catch (Exception $e) {
            throw new RuntimeException('Unable to read from stream', 0, $e);
        }

        if ($string === false) {
            throw new RuntimeException('Unable to read from stream');
        }

        return $string;
    }

    public function getContents(): string
    {
        /** @var string|false $contents */
        $contents = stream_get_contents($this->getStream());

        if ($contents === false) {
            throw new RuntimeException('Unable to read stream contents');
        }

        return $contents;
    }

    public function getMetadata(?string $key = null)
    {
        $stream = $this->getStream();

        if (!$key) {
            return stream_get_meta_data($stream);
        }

        $meta = stream_get_meta_data($stream);
        return $meta[$key] ?? null;
    }

    /**
     * @return resource
     */
    private function getStream()
    {
        if ($this->isClosed()) {
            throw new FileContentClosedException(
                "File's Content is already closed, try to fetch it right after File fetched from Reader."
            );
        }

        if (!isset($this->stream)) {
            $stream = ($this->contentClosure)();
            $this->stream = $stream;
            $this->contentClosure = null;
        }

        return $this->stream;
    }
}
