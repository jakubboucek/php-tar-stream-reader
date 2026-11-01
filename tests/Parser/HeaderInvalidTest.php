<?php

/** @testCase */

declare(strict_types=1);

namespace JakubBoucek\Tar\Tests\Parser;

use JakubBoucek\Tar\Exception\InvalidArgumentException;
use JakubBoucek\Tar\Exception\InvalidArchiveFormatException;
use JakubBoucek\Tar\Parser\Header;
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
            substr_replace($valid, 'not-an-octal', 124, 12),
        ] as $invalid) {
            Assert::exception(fn() => Header::fromTarString($invalid), InvalidArchiveFormatException::class);
        }
    }

    public function testConstructFromValues(): void
    {
        $header = new Header('file.txt', 3, '0');
        Assert::same('file.txt', $header->getName());
        Assert::same(3, $header->getSize());
        Assert::same('0', $header->getType());
        Assert::exception(fn() => new Header('file', -1, '0'), InvalidArgumentException::class);
        Assert::exception(fn() => new Header('file', 0, ''), InvalidArgumentException::class);
        Assert::exception(fn() => new Header('file', 0, 'xx'), InvalidArgumentException::class);
    }

    public function testPaxOverrideReplacesUnusablePhysicalSize(): void
    {
        $raw = file_get_contents(__DIR__ . '/../assets/header.bin');
        $raw = substr_replace($raw, str_repeat('?', 12), 124, 12);
        $header = Header::fromTarString($raw, 'override', 3);
        Assert::same('override', $header->getName());
        Assert::same(3, $header->getSize());
        foreach (['x', 'g'] as $type) {
            Assert::exception(
                fn() => Header::fromTarString(substr_replace($raw, $type, 156, 1), 'override', 3),
                InvalidArchiveFormatException::class,
            );
        }
        Assert::exception(
            fn() => Header::fromTarString(substr_replace($raw, 'broken', 257, 6), 'override', 3),
            InvalidArchiveFormatException::class,
        );
    }
}

new HeaderInvalidTest()->run();
