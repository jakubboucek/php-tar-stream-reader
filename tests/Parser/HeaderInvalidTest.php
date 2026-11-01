<?php

/** @testCase */

declare(strict_types=1);

namespace JakubBoucek\Tar\Tests\Parser;

use JakubBoucek\Tar\Exception\InvalidArgumentException;
use JakubBoucek\Tar\Exception\InvalidArchiveFormatException;
use JakubBoucek\Tar\Parser\Header;
use JakubBoucek\Tar\Parser\TarHeaderParser;
use JakubBoucek\Tar\EntryType;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

class HeaderInvalidTest extends TestCase
{
    public function testInvalidBinaryHeadersFailDuringParsing(): void
    {
        $valid = file_get_contents(__DIR__ . '/../assets/header.bin');
        foreach ([
            substr($valid, 0, 100),
            $valid . "\0",
            substr_replace($valid, 'broken', 257, 6),
            substr_replace($valid, 'Z', 156, 1),
            substr_replace($valid, 'not-an-octal', 124, 12),
        ] as $invalid) {
            Assert::exception(fn() => Header::fromTarString($invalid), InvalidArchiveFormatException::class);
        }
    }

    public function testConstructFromValues(): void
    {
        $header = new Header('file.txt', 3, EntryType::RegularFile);
        Assert::same('file.txt', $header->name);
        Assert::same(3, $header->size);
        Assert::same('0', $header->type->value);
        Assert::exception(fn() => new Header('file', -1, EntryType::RegularFile), InvalidArgumentException::class);
    }

    public function testPaxOverrideReplacesUnusablePhysicalSize(): void
    {
        $raw = file_get_contents(__DIR__ . '/../assets/header.bin');
        $raw = substr_replace($raw, str_repeat('?', 12), 124, 12);
        Assert::exception(fn() => Header::fromTarString($raw), InvalidArchiveFormatException::class);
        $header = TarHeaderParser::parse($raw, ['path' => 'override', 'size' => 3]);
        Assert::same('override', $header->name);
        Assert::same(3, $header->size);
        foreach (['x', 'g'] as $type) {
            Assert::exception(
                fn() => TarHeaderParser::parse(
                    substr_replace($raw, $type, 156, 1),
                    ['path' => 'override', 'size' => 3],
                ),
                InvalidArchiveFormatException::class,
            );
        }
        Assert::exception(
            fn() => TarHeaderParser::parse(substr_replace($raw, 'broken', 257, 6), ['path' => 'override', 'size' => 3]),
            InvalidArchiveFormatException::class,
        );
    }
}

new HeaderInvalidTest()->run();
