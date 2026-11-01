<?php

/** @testCase */

declare(strict_types=1);

namespace JakubBoucek\Tar\Tests\Parser;

use JakubBoucek\Tar\Parser\Header;
use JakubBoucek\Tar\EntryType;
use Tester\Assert;
use Tester\TestCase;

use function in_array;

require __DIR__ . '/../bootstrap.php';

class HeaderTest extends TestCase
{
    private Header $header;

    protected function setUp(): void
    {
        $header = file_get_contents(__DIR__ . '/../assets/header.bin');
        $this->header = new Header($header);
    }

    public function testTypeEnum(): void
    {
        $raw = file_get_contents(__DIR__ . '/../assets/header.bin');
        $cases = [
            '0' => EntryType::RegularFile,
            "\0" => EntryType::RegularFile,
            '1' => EntryType::HardLink,
            '2' => EntryType::SymbolicLink,
            '3' => EntryType::CharacterDevice,
            '4' => EntryType::BlockDevice,
            '5' => EntryType::Directory,
            '6' => EntryType::Fifo,
            '7' => EntryType::ContiguousFile,
            'x' => EntryType::PaxExtendedHeader,
            'g' => EntryType::PaxGlobalHeader,
            'Z' => null,
        ];
        foreach ($cases as $flag => $expected) {
            $flag = (string) $flag;
            $header = new Header(substr_replace($raw, $flag, 156, 1));
            Assert::same($expected, $header->getTypeEnum());
            Assert::same($flag, $header->getType());
            Assert::same($expected === EntryType::RegularFile, $header->isFile());
            Assert::same($expected === EntryType::Directory, $header->isDir());
        }
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
