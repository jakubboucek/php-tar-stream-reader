<?php

declare(strict_types=1);

namespace JakubBoucek\Tar\FileHandler;

interface IHandler
{
    /**
     * @param string $file
     * @return resource
     */
    public function open(string $file);

    /**
     * @param resource $handler
     */
    public function close($handler): void;
}
