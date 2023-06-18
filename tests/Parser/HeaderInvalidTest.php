<?php

declare(strict_types=1);

namespace JakubBoucek\Tar\Tests\Parser;

use JakubBoucek\Tar\Exception\InvalidArgumentException;
use JakubBoucek\Tar\Parser\Header;
use PHPUnit\Framework\TestCase;

class HeaderInvalidTest extends TestCase
{
    public function testInvalidInit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        /** @noinspection PhpUnhandledExceptionInspection */
        new Header(random_bytes(100));
    }
}
