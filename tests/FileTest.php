<?php

/** @testCase */

declare(strict_types=1);

namespace JakubBoucek\Tar\Tests;

use JakubBoucek\Tar\File;
use JakubBoucek\Tar\EntryType;
use JakubBoucek\Tar\Tests\Doubles\HeaderStub;
use JakubBoucek\Tar\Parser\LazyContent;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/bootstrap.php';

class FileTest extends TestCase
{
    private const TestName = 'bar';
    private const TestSize = 123456;
    private const TestType = '5';

    public function testToString(): void
    {
        $header = new HeaderStub(self::TestName, self::TestSize, self::TestType);
        $stream = new LazyContent(static function (): never {
            Assert::fail('File metadata access must not load content.');
        });

        $file = new File($header, $stream);

        Assert::same(self::TestName, (string) $file);
    }

    public function testGetName(): void
    {
        $header = new HeaderStub(self::TestName, self::TestSize, self::TestType);
        $stream = new LazyContent(static function (): never {
            Assert::fail('File metadata access must not load content.');
        });

        $file = new File($header, $stream);

        Assert::same(self::TestName, $file->getName());
    }

    public function testGetType(): void
    {
        $header = new HeaderStub(self::TestName, self::TestSize, self::TestType);
        $stream = new LazyContent(static function (): never {
            Assert::fail('File metadata access must not load content.');
        });

        $file = new File($header, $stream);

        Assert::same(self::TestType, $file->getType());
    }

    public function testGetTypeEnum(): void
    {
        $stream = new LazyContent(static function (): never {
            Assert::fail('File metadata access must not load content.');
        });
        $file = new File(new HeaderStub(self::TestName, self::TestSize, '5'), $stream);
        Assert::same(EntryType::Directory, $file->getTypeEnum());
        $unknown = new File(new HeaderStub(self::TestName, self::TestSize, 'Z'), $stream);
        Assert::null($unknown->getTypeEnum());
        Assert::same('Z', $unknown->getType());
    }

    public function testIsFile(): void
    {
        $header = new HeaderStub(self::TestName, self::TestSize, self::TestType);
        $stream = new LazyContent(static function (): never {
            Assert::fail('File metadata access must not load content.');
        });

        $file = new File($header, $stream);

        Assert::true($file->isFile());
        $header->regular = false;
        Assert::false($file->isFile());
    }

    public function testIsDir(): void
    {
        $header = new HeaderStub(self::TestName, self::TestSize, self::TestType);
        $stream = new LazyContent(static function (): never {
            Assert::fail('File metadata access must not load content.');
        });

        $file = new File($header, $stream);

        Assert::true($file->isDir());
        $header->directory = false;
        Assert::false($file->isDir());
    }

    public function testGetSize(): void
    {
        $header = new HeaderStub(self::TestName, self::TestSize, self::TestType);
        $stream = new LazyContent(static function (): never {
            Assert::fail('File metadata access must not load content.');
        });

        $file = new File($header, $stream);

        Assert::same(self::TestSize, $file->getSize());
    }

    public function testGetContent(): void
    {
        $header = new HeaderStub(self::TestName, self::TestSize, self::TestType);
        $stream = new LazyContent(static function (): never {
            Assert::fail('File metadata access must not load content.');
        });

        $file = new File($header, $stream);

        Assert::same($stream, $file->getContent());
    }
}

new FileTest()->run();
