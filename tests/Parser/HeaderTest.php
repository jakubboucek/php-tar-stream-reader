<?php

declare(strict_types=1);

namespace JakubBoucek\Tar\Tests\Parser;

use JakubBoucek\Tar\Parser\Header;
use PHPUnit\Framework\TestCase;

class HeaderTest extends TestCase
{
    private Header $header;

    protected function setUp(): void
    {
        $header = file_get_contents(__DIR__ . '/../assets/header.bin');
        $this->header = new Header($header);
    }

    public function testIsValid(): void
    {
        $this->assertTrue($this->header->isValid());
    }

    public function testIsDir(): void
    {
        $this->assertFalse($this->header->isDir());
    }

    public function testIsFile(): void
    {
        $this->assertTrue($this->header->isFile());
    }

    public function testGetSize(): void
    {
        $this->assertEquals(0, $this->header->getSize());
    }

    public function testGetName(): void
    {
        $this->assertEquals('empty.txt', $this->header->getName());
    }

    public function testGetType(): void
    {
        $type = $this->header->getType();
        $this->assertThat(
            $type,
            $this->logicalOr(
                $this->equalTo('0'),
                $this->equalTo("\0"),
            )
        );
    }
}
