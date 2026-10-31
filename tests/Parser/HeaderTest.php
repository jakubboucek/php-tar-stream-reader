<?php

/** @testCase */

declare(strict_types=1);

namespace JakubBoucek\Tar\Tests\Parser;

use JakubBoucek\Tar\Parser\Header;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

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
        Assert::true($this->header->isValid());
    }

    public function testIsDir(): void
    {
        Assert::false($this->header->isDir());
    }

    public function testIsFile(): void
    {
        Assert::true($this->header->isFile());
    }

    public function testGetSize(): void
    {
        Assert::same(0, $this->header->getSize());
    }

    public function testGetName(): void
    {
        Assert::same('empty.txt', $this->header->getName());
    }

    public function testGetType(): void
    {
        Assert::true(in_array($this->header->getType(), ['0', "\0"], true));
    }
}

new HeaderTest()->run();
