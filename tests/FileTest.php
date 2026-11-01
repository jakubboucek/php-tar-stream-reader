<?php

/** @testCase */

declare(strict_types=1);

namespace JakubBoucek\Tar\Tests;

use JakubBoucek\Tar\File;
use JakubBoucek\Tar\EntryType;
use JakubBoucek\Tar\Parser\Header;
use JakubBoucek\Tar\Parser\LazyContent;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/bootstrap.php';

class FileTest extends TestCase
{
    public function testMetadataDoesNotLoadContent(): void
    {
        $content = new LazyContent(static function (): never {
            Assert::fail('File metadata access must not load content.');
        });
        $file = new File(new Header('bar', 123456, EntryType::Directory), $content);
        Assert::same('bar', (string) $file);
        Assert::same('bar', $file->getName());
        Assert::same(123456, $file->getSize());
        Assert::same('5', $file->getType());
        Assert::same(EntryType::Directory, $file->getTypeEnum());
        Assert::same($content, $file->getContent());
    }

    public function testClassificationAndOriginalTypeByte(): void
    {
        $cases = [
            ['0', EntryType::RegularFile, true, false],
            ["\0", EntryType::ARegularFile, true, false],
            ['1', EntryType::HardLink, false, false],
            ['2', EntryType::SymbolicLink, false, false],
            ['3', EntryType::CharacterDevice, false, false],
            ['4', EntryType::BlockDevice, false, false],
            ['5', EntryType::Directory, false, true],
            ['6', EntryType::Fifo, false, false],
            ['7', EntryType::ContiguousFile, false, false],
            ['x', EntryType::PaxExtendedHeader, false, false],
            ['g', EntryType::PaxGlobalHeader, false, false],
        ];
        foreach ($cases as [$flag, $type, $isFile, $isDir]) {
            $content = new LazyContent(static function (): never {
                Assert::fail('File metadata access must not load content.');
            });
            $file = new File(new Header('entry', 0, $type), $content);
            Assert::same($flag, $file->getType());
            Assert::same($type, $file->getTypeEnum());
            Assert::same($isFile, $file->isFile());
            Assert::same($isDir, $file->isDir());
        }
    }
}

new FileTest()->run();
