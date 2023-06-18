<?php

declare(strict_types=1);

namespace JakubBoucek\Tar\Tests\Parser;

use JakubBoucek\Tar\Exception\InvalidArchiveFormatException;
use JakubBoucek\Tar\Parser\Header;
use PHPUnit\Framework\TestCase;

class HeaderNullTest extends TestCase
{
    private Header $header;

    protected function setUp(): void
    {
        $source = fopen(__DIR__ . '/../assets/nulls.bin', 'rb');
        $header = fread($source, 512);
        fclose($source);
        $this->header = new Header($header);
    }

    public function testIsValid(): void
    {
        $this->assertFalse($this->header->isValid());
    }

    public function testGetSize(): void
    {
        $this->expectException(InvalidArchiveFormatException::class);
        $this->header->getSize();
    }

}
