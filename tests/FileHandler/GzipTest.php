<?php

declare(strict_types=1);

namespace JakubBoucek\Tar\Tests\FileHandler;

use JakubBoucek\Tar\Exception\RuntimeException;
use JakubBoucek\Tar\FileHandler\Gzip;
use JakubBoucek\Tar\Tests\Exception\ExpectedError;
use PHPUnit\Framework\TestCase;

class GzipTest extends TestCase
{
    protected function setUp(): void
    {
        if (!Gzip::isAvailable()) {
            $this->markTestSkipped('Test requires `ext-bz2` extension to open BZ2 compressed archive.');
        }
    }

    public function testUnopenableWarning(): void
    {
        set_error_handler(
            static function ($errno, $errstr) {
                restore_error_handler();
                throw new ExpectedError($errstr, $errno);
            }
        );

        $this->expectException(ExpectedError::class);
        $this->expectExceptionCode(E_WARNING);
        (new Gzip())->open(__DIR__ . '/__@$non-exists');
    }

    public function testUnopenableException(): void
    {
        set_error_handler(
            static function ($errno, $errstr) {
                restore_error_handler();
                return (new ExpectedError($errstr, $errno))->isWarning();
            }
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Unable to open file \'.+\\/__@\\$non-exists\'/');
        (new Gzip())->open(__DIR__ . '/__@$non-exists');
    }

    public function testMatch(): void
    {
        $this->assertTrue(Gzip::match('file.tar.gz'));
        $this->assertTrue(Gzip::match('file.tgz'));
    }

    public function testMatchMiss(): void
    {
        $this->assertFalse(Gzip::match('file.tar'));
        $this->assertFalse(Gzip::match('file.tar.bz2'));
    }

    public function testOpen(): void
    {
        $handler = new Gzip();
        $stream = $handler->open(__DIR__ . '/../assets/gzipped.tgz');
        $this->assertEquals('empty.txt', fread($stream, 9));
        $handler->close($stream);
    }
}
