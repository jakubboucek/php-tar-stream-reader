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
        $this->header = Header::fromTarString($header);
    }

    public function testTypeEnum(): void
    {
        $raw = file_get_contents(__DIR__ . '/../assets/header.bin');
        $cases = [
            '0' => EntryType::RegularFile,
            "\0" => EntryType::ARegularFile,
            '1' => EntryType::HardLink,
            '2' => EntryType::SymbolicLink,
            '3' => EntryType::CharacterDevice,
            '4' => EntryType::BlockDevice,
            '5' => EntryType::Directory,
            '6' => EntryType::Fifo,
            '7' => EntryType::ContiguousFile,
            'x' => EntryType::PaxExtendedHeader,
            'g' => EntryType::PaxGlobalHeader,
        ];
        foreach ($cases as $flag => $expected) {
            $flag = (string) $flag;
            $header = Header::fromTarString(substr_replace($raw, $flag, 156, 1));
            Assert::same($expected, $header->type);
            Assert::same($flag, $header->type->value);
            Assert::same($expected->isFile(), $header->type->isFile());
            Assert::same($expected === EntryType::Directory, $header->type->isDir());
        }
    }

    public function testIsDir(): void
    {
        Assert::false($this->header->type->isDir());
    }

    public function testIsFile(): void
    {
        Assert::true($this->header->type->isFile());
    }

    public function testGetSize(): void
    {
        Assert::same(0, $this->header->size);
    }

    public function testGetName(): void
    {
        Assert::same('empty.txt', $this->header->name);
    }

    public function testGetType(): void
    {
        Assert::true(in_array($this->header->type->value, ['0', "\0"], true));
    }
}

new HeaderTest()->run();
