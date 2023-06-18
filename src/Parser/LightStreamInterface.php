<?php

declare(strict_types=1);

namespace JakubBoucek\Tar\Parser;

use Psr\Http\Message\StreamInterface;

interface LightStreamInterface extends StreamInterface
{
    /**
     * @param resource $stream
     * @return void
     */
    public function toStream($stream): void;

    /**
     * @param resource|null $context Stream context (e.g. from `stream_context_create()`)
     */
    public function toFile(string $file, $context = null): void;
}
