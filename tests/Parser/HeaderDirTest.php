<?php

/** @testCase */

declare(strict_types=1);

namespace JakubBoucek\Tar\Tests\Parser;

use JakubBoucek\Tar\Parser\Header;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

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
        Assert::true($this->header->isValid());
    }

    public function testIsDir(): void
    {
        Assert::true($this->header->isDir());
    }

    public function testIsFile(): void
    {
        Assert::false($this->header->isFile());
    }

    public function testGetSize(): void
    {
        Assert::same(0, $this->header->getSize());
    }

    public function testGetName(): void
    {
        Assert::same('some-dir/', $this->header->getName());
    }

    public function testGetType(): void
    {
        $type = $this->header->getType();
        Assert::same('5', $type);
    }
}

new HeaderDirTest()->run();
