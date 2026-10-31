<?php

/** @testCase */

declare(strict_types=1);

namespace JakubBoucek\Tar\Tests;

use JakubBoucek\Tar\Exception\InvalidArchiveFormatException;
use JakubBoucek\Tar\FileHandler\Plain;
use JakubBoucek\Tar\FileReader;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/bootstrap.php';

class FileReaderTest extends TestCase
{
    public function testClosesAfterExhaustion(): void
    {
        $handler = new TrackingPlainHandler();
        Assert::same(5, iterator_count(new FileReader(__DIR__ . '/assets/plain.tar', $handler)));
        $this->assertClosed($handler);
    }

    public function testClosesAfterBreak(): void
    {
        $handler = new TrackingPlainHandler();
        foreach (new FileReader(__DIR__ . '/assets/plain.tar', $handler) as $file) {
            break;
        }
        $this->assertClosed($handler);
    }

    public function testClosesAfterConsumerException(): void
    {
        $handler = new TrackingPlainHandler();
        $failure = new \RuntimeException('Consumer failed');
        Assert::same($failure, Assert::exception(function () use ($handler, $failure): void {
            foreach (new FileReader(__DIR__ . '/assets/plain.tar', $handler) as $file) {
                throw $failure;
            }
        }, \RuntimeException::class));
        $this->assertClosed($handler);
    }

    public function testClosesAfterParserException(): void
    {
        $filename = tempnam(sys_get_temp_dir(), 'TarInvalidHeader');
        $handler = new TrackingPlainHandler();
        try {
            file_put_contents($filename, 'short header');
            Assert::exception(
                fn() => iterator_count(new FileReader($filename, $handler)),
                InvalidArchiveFormatException::class,
            );
            $this->assertClosed($handler);
        } finally {
            unlink($filename);
        }
    }

    private function assertClosed(TrackingPlainHandler $handler): void
    {
        try {
            Assert::same(1, $handler->closeCalls);
            Assert::same('resource (closed)', gettype($handler->stream));
        } finally {
            if (is_resource($handler->stream)) {
                fclose($handler->stream);
            }
        }
    }
}

class TrackingPlainHandler extends Plain
{
    public int $closeCalls = 0;
    /** @var resource|null */
    public $stream;

    public function open(string $filename)
    {
        return $this->stream = parent::open($filename);
    }

    public function close($stream): void
    {
        $this->closeCalls++;
        parent::close($stream);
    }
}

new FileReaderTest()->run();
