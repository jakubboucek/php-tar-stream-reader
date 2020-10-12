<?php

declare(strict_types=1);

namespace JakubBoucek\Tar;

use IteratorAggregate;
use JakubBoucek\Tar\FileHandler\GzFileHandler;
use JakubBoucek\Tar\FileHandler\TarFileHandler;
use JakubBoucek\Tar\Parser\ArchiveIterator;
use RuntimeException;

/**
 * @implements IteratorAggregate<FileInfo>
 */
class ArchiveReader implements IteratorAggregate
{
    public const MODE_READ = true;
    public const MODE_SCAN = false;

    public const TYPE_TAR = false;
    public const TYPE_GZ = true;
    public const TYPE_AUTO = null;

    /** @var string */
    private $file;

    /** @var bool */
    private $mode;

    /** @var bool */
    private $type;

    public function __construct(string $file, ?bool $type = self::TYPE_AUTO, bool $mode = self::MODE_READ)
    {
        if (is_readable($file) === false) {
            throw new RuntimeException("Unable to read file \'{$file}\'");
        }

        $this->file = $file;
        $this->mode = $mode;
        $this->type = $type ?? $this->isGzipped($file);
    }

    public static function read(string $file, ?bool $type = self::TYPE_AUTO): self
    {
        return new self($file, $type, self::MODE_READ);
    }

    public static function scan(string $file, ?bool $type = self::TYPE_AUTO): self
    {
        return new self($file, $type, self::MODE_SCAN);
    }

    public function getIterator(): ArchiveIterator
    {
        $handler = $this->type === self::TYPE_GZ ? new GzFileHandler() : new TarFileHandler();

        return new ArchiveIterator($this->file, $handler, $this->mode);
    }

    private function isGzipped(string $file): bool
    {
        return preg_match('/\.t?gz$/D', $file) === 1;
    }
}
