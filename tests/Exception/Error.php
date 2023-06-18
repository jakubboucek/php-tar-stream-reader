<?php

declare(strict_types=1);

namespace JakubBoucek\Tar\Tests\Exception;

use Exception;

class Error extends Exception
{
    public function getSeverityKey(): string
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return match ($this->getCode()) {
            E_ERROR => 'E_ERROR',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_STRICT => 'E_STRICT',
            E_DEPRECATED => 'E_DEPRECATED',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
            default => throw new Exception("Unknown error severity: {$this->getCode()}"),
        };
    }

    public function isSeverity(int $levels): bool
    {
        return (bool)($this->getCode() & $levels);
    }

    public function isError(): bool
    {
        return $this->isSeverity(
            E_ERROR | E_RECOVERABLE_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR
        );
    }

    public function isWarning(): bool
    {
        return $this->isSeverity(E_WARNING | E_CORE_WARNING | E_COMPILE_WARNING | E_USER_WARNING);
    }

    public function isNotice(): bool
    {
        return $this->isSeverity(E_NOTICE | E_USER_NOTICE);
    }

    public function isDeprecated(): bool
    {
        return $this->isSeverity(E_DEPRECATED | E_USER_DEPRECATED);
    }

    public function isStrictNote(): bool
    {
        return $this->isSeverity(E_STRICT);
    }
}
