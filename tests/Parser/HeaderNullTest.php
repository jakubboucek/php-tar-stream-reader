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
    private Header $header;

    protected function setUp(): void
    {
        $source = fopen(__DIR__ . '/../assets/nulls.bin', 'rb');
        $header = fread($source, 512);
        fclose($source);
        $this->header = new Header($header);
    }

    public function testIsValid(): void
    {
        Assert::false($this->header->isValid());
    }

    public function testGetSize(): void
    {
        Assert::exception(
            fn() => $this->header->getSize(),
            InvalidArchiveFormatException::class,
        );
    }
}

(new HeaderNullTest())->run();
