<?php

declare(strict_types=1);

namespace JakubBoucek\Tar\Tests\Parser;

use JakubBoucek\Tar\Exception\FileContentClosedException;
use JakubBoucek\Tar\Parser\Usage;
use PHPUnit\Framework\TestCase;

class UsageTest extends TestCase
{
    private Usage $usage;

    protected function setUp(): void
    {
        $this->usage = new Usage();
    }

    public function testNotUsed(): void
    {
        $this->assertFalse($this->usage->used());
    }

    public function testUsed(): void
    {
        $this->usage->use();
        $this->assertTrue($this->usage->used());
    }

    public function testDoubleUse(): void
    {
        $this->expectException(FileContentClosedException::class);
        $this->usage->use();
        $this->usage->use();
    }
}
