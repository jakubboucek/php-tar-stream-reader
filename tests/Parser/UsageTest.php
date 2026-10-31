<?php
/** @testCase */

declare(strict_types=1);

namespace JakubBoucek\Tar\Tests\Parser;

use JakubBoucek\Tar\Exception\FileContentClosedException;
use JakubBoucek\Tar\Parser\Usage;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

class UsageTest extends TestCase
{
    private Usage $usage;

    protected function setUp(): void
    {
        $this->usage = new Usage();
    }

    public function testNotUsed(): void
    {
        Assert::false($this->usage->used());
    }

    public function testUsed(): void
    {
        $this->usage->use();
        Assert::true($this->usage->used());
    }

    public function testDoubleUse(): void
    {
        $this->usage->use();

        Assert::exception(
            fn() => $this->usage->use(),
            FileContentClosedException::class,
        );
    }
}

(new UsageTest())->run();
