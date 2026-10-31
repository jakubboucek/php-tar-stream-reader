<?php

/** @testCase */

declare(strict_types=1);

namespace JakubBoucek\Tar\Tests\FileHandler;

use JakubBoucek\Tar\Exception\RuntimeException;
use JakubBoucek\Tar\FileHandler\Gzip;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

class GzipTest extends TestCase
{
    protected function setUp(): void
    {
        if (!Gzip::isAvailable()) {
            $this->skip('Test requires `ext-bz2` extension to open BZ2 compressed archive.');
        }
    }

    public function testUnopenableWarning(): void
    {
        Assert::error(
            fn() => Assert::exception(
                fn() => new Gzip()->open(__DIR__ . '/__@$non-exists'),
                RuntimeException::class,
            ),
            E_WARNING,
        );
    }

    public function testUnopenableException(): void
    {
        Assert::error(
            fn() => Assert::exception(
                fn() => new Gzip()->open(__DIR__ . '/__@$non-exists'),
                RuntimeException::class,
                "Unable to open file '%a%/__@\$non-exists'",
            ),
            E_WARNING,
        );
    }

    public function testMatch(): void
    {
        Assert::true(Gzip::match('file.tar.gz'));
        Assert::true(Gzip::match('file.tgz'));
    }

    public function testMatchMiss(): void
    {
        Assert::false(Gzip::match('file.tar'));
        Assert::false(Gzip::match('file.tar.bz2'));
    }

    public function testOpen(): void
    {
        $handler = new Gzip();
        $stream = $handler->open(__DIR__ . '/../assets/gzipped.tgz');
        try {
            Assert::same('empty.txt', fread($stream, 9));
        } finally {
            $handler->close($stream);
        }
    }
}

new GzipTest()->run();
