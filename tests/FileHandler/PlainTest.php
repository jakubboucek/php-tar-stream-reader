<?php
/** @testCase */

declare(strict_types=1);

namespace JakubBoucek\Tar\Tests\FileHandler;

use JakubBoucek\Tar\Exception\RuntimeException;
use JakubBoucek\Tar\FileHandler\Plain;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

class PlainTest extends TestCase
{
    public function testOpen(): void
    {
        $handler = new Plain();
        $stream = $handler->open(__DIR__ . '/../assets/plain.tar');
        try {
            Assert::same('empty.txt', fread($stream, 9));
        } finally {
            $handler->close($stream);
        }
    }

    public function testUnopenableWarning(): void
    {
        Assert::error(
            fn() => Assert::exception(
                fn() => (new Plain())->open(__DIR__ . '/__@$non-exists'),
                RuntimeException::class,
            ),
            E_WARNING,
        );
    }

    public function testUnopenableException(): void
    {
        Assert::error(
            fn() => Assert::exception(
                fn() => (new Plain())->open(__DIR__ . '/__@$non-exists'),
                RuntimeException::class,
                "Unable to open file '%a%/__@\$non-exists'",
            ),
            E_WARNING,
        );
    }
}

(new PlainTest())->run();
