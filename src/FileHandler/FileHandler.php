<?php

declare(strict_types=1);

namespace JakubBoucek\Tar\FileHandler;

interface FileHandler
{
    /**
     * @param string $filename
     * @return resource
     */
    public function open(string $filename);

    /**
     * @param resource $stream
     */
    public function close($stream): void;
}
