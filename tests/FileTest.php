<?php

declare(strict_types=1);

namespace JakubBoucek\Tar\Tests;

use JakubBoucek\Tar\File;
use JakubBoucek\Tar\Parser\Header;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class FileTest extends TestCase
{
    private const TestContent = 'foo';
    private const TestName = 'bar';
    private const TestSize = 123456;
    private const TestType = '5';

    public function test__toString(): void
    {
        $header = $this->createStub(Header::class);
        $stream = $this->createStub(StreamInterface::class);

        $header->method('getName')->willReturn(self::TestName);

        $file = new File($header, $stream);

        $this->assertEquals(self::TestName, (string)$file);
    }

    public function testGetName(): void
    {
        $header = $this->createStub(Header::class);
        $stream = $this->createStub(StreamInterface::class);

        $header->method('getName')->willReturn(self::TestName);

        $file = new File($header, $stream);

        $this->assertEquals(self::TestName, $file->getName());
    }

    public function testGetType(): void
    {
        $header = $this->createStub(Header::class);
        $stream = $this->createStub(StreamInterface::class);

        $header->method('getType')->willReturn(self::TestType);

        $file = new File($header, $stream);

        $this->assertEquals(self::TestType, $file->getType());
    }

    public function testIsFile(): void
    {
        $header = $this->createStub(Header::class);
        $stream = $this->createStub(StreamInterface::class);

        $header->method('isFile')->willReturn(true);

        $file = new File($header, $stream);

        $this->assertTrue($file->isFile());
    }

    public function testIsDir(): void
    {
        $header = $this->createStub(Header::class);
        $stream = $this->createStub(StreamInterface::class);

        $header->method('isDir')->willReturn(true);

        $file = new File($header, $stream);

        $this->assertTrue($file->isDir());
    }

    public function testGetSize(): void
    {
        $header = $this->createStub(Header::class);
        $stream = $this->createStub(StreamInterface::class);

        $header->method('getSize')->willReturn(self::TestSize);

        $file = new File($header, $stream);

        $this->assertEquals(self::TestSize, $file->getSize());
    }

    public function testGetContent(): void
    {
        $header = $this->createStub(Header::class);
        $stream = $this->createStub(StreamInterface::class);

        $header->method('getName')->willReturn(self::TestContent);

        $file = new File($header, $stream);

        $this->assertEquals($stream, $file->getContent());
    }
}
