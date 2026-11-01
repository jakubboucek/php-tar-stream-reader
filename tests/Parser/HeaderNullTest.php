<?php

/** @testCase */

declare(strict_types=1);

namespace JakubBoucek\Tar\Tests\Parser;

use JakubBoucek\Tar\Exception\InvalidArchiveFormatException;
use JakubBoucek\Tar\Parser\Header;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

class HeaderNullTest extends TestCase
{
    public function testNullBlockIsNotAnEntryHeader(): void
    {
        Assert::exception(
            fn() => Header::fromTarString(file_get_contents(__DIR__ . '/../assets/nulls.bin')),
            InvalidArchiveFormatException::class,
        );
    }
}

new HeaderNullTest()->run();
