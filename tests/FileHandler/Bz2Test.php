<?php

declare(strict_types=1);

namespace JakubBoucek\Tar\Tests\FileHandler;

use JakubBoucek\Tar\Exception\RuntimeException;
use JakubBoucek\Tar\FileHandler\Bz2;
use JakubBoucek\Tar\Tests\Exception\ExpectedError;
use PHPUnit\Framework\TestCase;

class Bz2Test extends TestCase
{
    protected function setUp(): void
    {
        if (!Bz2::isAvailable()) {
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
        (new Bz2())->open(__DIR__ . '/__@$non-exists');
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
        (new Bz2())->open(__DIR__ . '/__@$non-exists');
    }

    public function testMatch(): void
    {
        $this->assertTrue(Bz2::match('file.tar.bz'));
        $this->assertTrue(Bz2::match('file.tar.bz2'));
        $this->assertTrue(Bz2::match('file.tbz'));
        $this->assertTrue(Bz2::match('file.tbz2'));
    }

    public function testMatchMiss(): void
    {
        $this->assertFalse(Bz2::match('file.tar'));
        $this->assertFalse(Bz2::match('file.tar.gz'));
    }

    public function testOpen(): void
    {
        $handler = new Bz2();
        $stream = $handler->open(__DIR__ . '/../assets/bzipped.tbz');
        $this->assertEquals('empty.txt', fread($stream, 9));
        $handler->close($stream);
    }
}
