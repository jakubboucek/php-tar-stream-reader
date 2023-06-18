<?php

declare(strict_types=1);

namespace JakubBoucek\Tar\Tests\FileHandler;

use JakubBoucek\Tar\Exception\RuntimeException;
use JakubBoucek\Tar\FileHandler\Plain;
use JakubBoucek\Tar\Tests\Exception\ExpectedError;
use PHPUnit\Framework\TestCase;

class PlainTest extends TestCase
{
    public function testOpen(): void
    {
        $handler = new Plain();
        $stream = $handler->open(__DIR__ . '/../assets/plain.tar');
        $this->assertEquals('empty.txt', fread($stream, 9));
        $handler->close($stream);
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
        (new Plain())->open(__DIR__ . '/__@$non-exists');
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
        (new Plain())->open(__DIR__ . '/__@$non-exists');
    }
}
