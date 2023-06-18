<?php

declare(strict_types=1);

namespace JakubBoucek\Tar\Tests\Parser;

use JakubBoucek\Tar\Parser\Header;
use PHPUnit\Framework\TestCase;

class HeaderDirTest extends TestCase
{
    private Header $header;

    protected function setUp(): void
    {
        $source = fopen(__DIR__ . '/../assets/dir.tar', 'rb');
        $header = fread($source, 512);
        fclose($source);
        $this->header = new Header($header);
    }

    public function testIsValid(): void
    {
        $this->assertTrue($this->header->isValid());
    }

    public function testIsDir(): void
    {
        $this->assertTrue($this->header->isDir());
    }

    public function testIsFile(): void
    {
        $this->assertFalse($this->header->isFile());
    }

    public function testGetSize(): void
    {
        $this->assertEquals(0, $this->header->getSize());
    }

    public function testGetName(): void
    {
        $this->assertEquals('some-dir/', $this->header->getName());
    }

    public function testGetType(): void
    {
        $type = $this->header->getType();
        $this->assertEquals('5', $type);
    }
}
