<?php

/** @testCase */

declare(strict_types=1);

namespace JakubBoucek\Tar\Tests\FileHandler;

use JakubBoucek\Tar\Exception\RuntimeException;
use JakubBoucek\Tar\FileHandler\Bz2;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

class Bz2Test extends TestCase
{
    protected function setUp(): void
    {
        if (!Bz2::isAvailable()) {
            $this->skip('Test requires `ext-bz2` extension to open BZ2 compressed archive.');
        }
    }

    public function testUnopenableWarning(): void
    {
        Assert::error(
            fn() => Assert::exception(
                fn() => new Bz2()->open(__DIR__ . '/__@$non-exists'),
                RuntimeException::class,
            ),
            E_WARNING,
        );
    }

    public function testUnopenableException(): void
    {
        Assert::error(
            fn() => Assert::exception(
                fn() => new Bz2()->open(__DIR__ . '/__@$non-exists'),
                RuntimeException::class,
                "Unable to open file '%a%/__@\$non-exists'",
            ),
            E_WARNING,
        );
    }

    public function testMatch(): void
    {
        Assert::true(Bz2::match('file.tar.bz'));
        Assert::true(Bz2::match('file.tar.bz2'));
        Assert::true(Bz2::match('file.tbz'));
        Assert::true(Bz2::match('file.tbz2'));
    }

    public function testMatchMiss(): void
    {
        Assert::false(Bz2::match('file.tar'));
        Assert::false(Bz2::match('file.tar.gz'));
    }

    public function testOpen(): void
    {
        $handler = new Bz2();
        $stream = $handler->open(__DIR__ . '/../assets/bzipped.tbz');
        try {
            Assert::same('empty.txt', fread($stream, 9));
        } finally {
            $handler->close($stream);
        }
    }
}

new Bz2Test()->run();
