<?php

namespace JakubBoucek\Tar\Tests\ResourceGenerators;

use Generator;
use JakubBoucek\Tar\Exception\RuntimeException;

class MassiveFileStreamWrapper
{
    public $context;
    protected Generator $generator;
    protected string $buffer = '';
    protected int $position = 0;

    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        $options = stream_context_get_options($this->context);
        if (!array_key_exists('massive-file', $options)) {
            return false;
        }
        $options = $options['massive-file'];

        if (!array_key_exists('generator', $options)) {
            return false;
        }
        $this->generator = $options['generator'];

        return true;
    }

    public function stream_eof(): bool
    {
        return !$this->generator->valid() && strlen($this->buffer) === 0;
    }

    public function stream_read(int $count): string
    {
        while ($this->generator->valid() && strlen($this->buffer) < $count) {
            $this->buffer .= $this->generator->current();
            $this->generator->next();
        }
        $data = substr($this->buffer, 0, $count);
        $this->buffer = substr($this->buffer, $count);
        $this->position += strlen($data);
        return $data;
    }

    public function stream_tell(): int
    {
        return $this->position;
    }

    public function stream_seek(int $offset, int $whence): bool
    {
        if ($whence === SEEK_END) {
            throw new RuntimeException('Seeking from the end is not supported');
        }

        if (
            ($whence === SEEK_CUR && $offset < 0)
            || ($whence === SEEK_SET && $offset < $this->position)
        ) {
            throw new RuntimeException('Seeking backwards is not supported');
        }

        $newPosition = $whence === SEEK_CUR ? $this->position + $offset : $offset;
        while ($this->position < $newPosition) {
            // Limit read to 1 MiB, to reduce memory usage
            $this->stream_read(min($newPosition - $this->position, 1204 ** 2));
        }
        return true;
    }

    public function stream_stat(): array
    {
        return [];
    }
}

stream_wrapper_register('massive-file', MassiveFileStreamWrapper::class)
|| throw new \RuntimeException('Failed to register stream wrapper ' . MassiveFileStreamWrapper::class);
