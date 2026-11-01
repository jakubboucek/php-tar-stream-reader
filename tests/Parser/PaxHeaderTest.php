<?php

/** @testCase */

declare(strict_types=1);

namespace JakubBoucek\Tar\Tests\Parser;

use JakubBoucek\Tar\Exception\InvalidArchiveFormatException;
use JakubBoucek\Tar\Exception\NotImplementedException;
use JakubBoucek\Tar\Parser\PaxHeader;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

class PaxHeaderTest extends TestCase
{
    public function testSupportedValues(): void
    {
        Assert::same(['size' => 3, 'path' => 'abc'], PaxHeader::parse("9 size=3\n12 path=abc\n"));
        Assert::same(['size' => 0], PaxHeader::parse("9 size=0\n"));
        Assert::same(['size' => 8589934592], PaxHeader::parse("19 size=8589934592\n"));
        Assert::same(['size' => 3], PaxHeader::parse("9 size=0\n9 size=3\n"));
        Assert::same(['path' => "a\nb=c"], PaxHeader::parse("14 path=a\nb=c\n"));
        Assert::same([], PaxHeader::parse("26 VENDOR.note=a\n9 size=0\n"));
        Assert::same([], PaxHeader::parse("19 comment=fixture\n", true));
    }

    public function testMalformedRecords(): void
    {
        foreach ([
            '',
            "1 size=3\n",
            "99 size=3\n",
            "9 size=3",
            "9 size=3!",
            "x size=3\n",
            "9 size=-1\n",
            "10 size=-1\n",
            "16 size=3garbage\n",
            "8 =test\n",
            "9 noequals\n",
            "999999999999999999999999999999 size=3\n",
            "9 size=3\n\n",
            "31 size=9999999999999999999999\n",
        ] as $payload) {
            Assert::exception(fn() => PaxHeader::parse($payload), InvalidArchiveFormatException::class);
        }
    }

    public function testUnsupportedRecords(): void
    {
        foreach (["9 size=3\n", "8 size=\n", "12 path=abc\n", "8 path=\n"] as $payload) {
            Assert::exception(fn() => PaxHeader::parse($payload, true), NotImplementedException::class);
        }
        foreach (["8 size=\n", "8 path=\n"] as $payload) {
            Assert::exception(fn() => PaxHeader::parse($payload), NotImplementedException::class);
        }
        Assert::exception(
            fn() => PaxHeader::parse(str_repeat('x', PaxHeader::MaxPayloadSize + 1)),
            NotImplementedException::class,
        );
    }
}

new PaxHeaderTest()->run();
