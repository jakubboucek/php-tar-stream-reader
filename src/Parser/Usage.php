<?php

declare(strict_types=1);

namespace JakubBoucek\Tar\Parser;

use JakubBoucek\Tar\Exception\FileContentClosedException;

class Usage
{
    private bool $used = false;

    public function used(): bool
    {
        return $this->used;
    }

    public function use(): void
    {
        if ($this->used) {
            throw new FileContentClosedException(
                "File's Content is already closed, try to fetch it right after File fetched from Reader."
            );
        }

        $this->used = true;
    }
}
