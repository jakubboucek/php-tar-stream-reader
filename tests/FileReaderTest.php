<?php

/** @testCase */

declare(strict_types=1);

namespace JakubBoucek\Tar\Tests;

use JakubBoucek\Tar\Exception\InvalidArchiveFormatException;
use JakubBoucek\Tar\Exception\FileContentClosedException;
use JakubBoucek\Tar\FileHandler\Plain;
use JakubBoucek\Tar\FileReader;
use Tester\Assert;
use Tester\TestCase;

use function gettype;
use function is_resource;

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
        $content = null;
        Assert::same($failure, Assert::exception(function () use ($handler, $failure, &$content): void {
            foreach (new FileReader(__DIR__ . '/assets/plain.tar', $handler) as $file) {
                $content = $file->getContent();
                throw $failure;
            }
        }, \RuntimeException::class));
        Assert::true($content->isClosed());
        $this->assertClosed($handler);
    }

    public function testUnreadContentAfterBreakPreservesDestination(): void
    {
        $archive = tempnam(sys_get_temp_dir(), 'TarUnread');
        $destination = tempnam(sys_get_temp_dir(), 'TarDestination');
        try {
            // Missing payload must not be read while destroying the generator.
            $header = file_get_contents(__DIR__ . '/assets/header.bin');
            file_put_contents($archive, substr_replace($header, "00000000003\0", 124, 12));
            file_put_contents($destination, 'Keep existing content');
            foreach (new FileReader($archive) as $file) {
                break;
            }

            Assert::exception(
                fn() => $file->getContent()->toFile($destination),
                FileContentClosedException::class,
            );
            Assert::same('Keep existing content', file_get_contents($destination));
            Assert::true($file->getContent()->isClosed());
        } finally {
            unlink($archive);
            unlink($destination);
        }
    }

    public function testLoadedContentSurvivesBreak(): void
    {
        foreach (new FileReader(__DIR__ . '/assets/plain.tar') as $file) {
            if ($file->getSize() === 0) {
                continue;
            }
            $content = $file->getContent();
            $expected = $content->getContents();
            break;
        }
        try {
            Assert::notSame('', $expected);
            Assert::false($content->isClosed());
            $content->rewind();
            Assert::same($expected, $content->getContents());
        } finally {
            $content->close();
        }
    }

    public function testUnreadContentClosesWhenRetainedGeneratorIsReleased(): void
    {
        $handler = new TrackingPlainHandler();
        $iterator = new FileReader(__DIR__ . '/assets/plain.tar', $handler)->getIterator();
        try {
            foreach ($iterator as $file) {
                if ($file->getSize() > 0) {
                    break;
                }
            }
            Assert::false($file->getContent()->isClosed());
            Assert::same(0, $handler->closeCalls);
        } finally {
            unset($iterator);
        }
        Assert::true($file->getContent()->isClosed());
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

    /**
     * @inheritDoc
     */
    public function open(string $filename)
    {
        return $this->stream = parent::open($filename);
    }

    /**
     * @inheritDoc
     */
    public function close($stream): void
    {
        $this->closeCalls++;
        parent::close($stream);
    }
}

new FileReaderTest()->run();
