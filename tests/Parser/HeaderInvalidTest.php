<?php

/** @testCase */

declare(strict_types=1);

namespace JakubBoucek\Tar\Tests\Parser;

use JakubBoucek\Tar\Exception\InvalidArgumentException;
use JakubBoucek\Tar\Parser\Header;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

class HeaderInvalidTest extends TestCase
{
    public function testInvalidInit(): void
    {
        Assert::exception(
            fn() => new Header(random_bytes(100)),
            InvalidArgumentException::class,
        );
    }
}

new HeaderInvalidTest()->run();
