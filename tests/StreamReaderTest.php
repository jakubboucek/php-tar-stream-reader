<?php
/** @testCase */

declare(strict_types=1);

namespace JakubBoucek\Tar\Tests;

use JakubBoucek\Tar\File;
use JakubBoucek\Tar\Exception\FileContentClosedException;
use JakubBoucek\Tar\Exception\InvalidArchiveFormatException;
use JakubBoucek\Tar\StreamReader;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/bootstrap.php';

class StreamReaderTest extends TestCase
{
    public function testTraverseHeads(): void
    {
        $stream = gzopen(__DIR__ . '/assets/test-archive-null.tgz', 'rb');
        try {
            $patterns = json_decode(
                file_get_contents(__DIR__ . '/assets/test-archive-null.json'),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            $iterator = (new StreamReader($stream))->getIterator();

            foreach ($patterns as $pattern) {
                /** @noinspection DisconnectedForeachInstructionInspection */
                Assert::true($iterator->valid());

                /** @var File $file */
                $file = $iterator->current();
                Assert::same($pattern['file'], $file->getName());
                Assert::same($pattern['length'], $file->getSize());

                /** @noinspection DisconnectedForeachInstructionInspection */
                $iterator->next();
            }

            Assert::false($iterator->valid());
        } finally {
            gzclose($stream);
        }
    }

    public function testTraverseContents(): void
    {
        $stream = gzopen(__DIR__ . '/assets/test-archive-random.tgz', 'rb');
        try {
            $patterns = json_decode(
                file_get_contents(__DIR__ . '/assets/test-archive-random.json'),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            $iterator = (new StreamReader($stream))->getIterator();

            foreach ($patterns as $pattern) {
                /** @noinspection DisconnectedForeachInstructionInspection */
                Assert::true($iterator->valid());

                /** @var File $file */
                $file = $iterator->current();
                Assert::same($pattern['hash'], md5((string)$file->getContent()));

                /** @noinspection DisconnectedForeachInstructionInspection */
                $iterator->next();
            }

            Assert::false($iterator->valid());
        } finally {
            gzclose($stream);
        }
    }

    public function testSkippedContentIsClosed(): void
    {
        $stream = gzopen(__DIR__ . '/assets/test-archive-random.tgz', 'rb');
        try {
            $iterator = (new StreamReader($stream))->getIterator();
            $content = $iterator->current()->getContent();
            $iterator->next();
            Assert::true($content->isClosed());
            Assert::exception(fn() => $content->getContents(), FileContentClosedException::class);
        } finally {
            gzclose($stream);
        }
    }

    public function testShortHeader(): void
    {
        $this->assertInvalidArchive(str_repeat('x', 511));
    }

    public function testInvalidMagic(): void
    {
        $header = file_get_contents(__DIR__ . '/assets/header.bin');
        $this->assertInvalidArchive(substr_replace($header, 'wrong!', 257, 6));
    }

    public function testTruncatedContent(): void
    {
        $header = file_get_contents(__DIR__ . '/assets/header.bin');
        $header = substr_replace($header, "00000000003\0", 124, 12);
        $this->assertInvalidArchive($header . 'ab');
    }

    /** @dataProvider truncatedArchives */
    public function testTruncatedArchive(string $compression, string $payload, bool $readContent): void
    {
        if ($compression === 'bzip2' && !extension_loaded('bz2')) {
            $this->skip('The bz2 extension is required.');
        }
        $header = file_get_contents(__DIR__ . '/assets/header.bin');
        $archive = substr_replace($header, "00000000003\0", 124, 12) . $payload;
        $encoded = match ($compression) {
            'gzip' => gzencode($archive),
            'bzip2' => bzcompress($archive),
            default => $archive,
        };
        $handler = match ($compression) {
            'gzip' => new \JakubBoucek\Tar\FileHandler\Gzip(),
            'bzip2' => new \JakubBoucek\Tar\FileHandler\Bz2(),
            default => new \JakubBoucek\Tar\FileHandler\Plain(),
        };
        $filename = tempnam(sys_get_temp_dir(), 'TarTruncated');
        try {
            file_put_contents($filename, $encoded);
            Assert::exception(function () use ($filename, $handler, $readContent): void {
                foreach (new \JakubBoucek\Tar\FileReader($filename, $handler) as $file) {
                    if ($readContent) {
                        $file->getContent()->getContents();
                    }
                }
            }, InvalidArchiveFormatException::class);
        } finally {
            unlink($filename);
        }
    }

    public function truncatedArchives(): array
    {
        $cases = [];
        foreach (['plain', 'gzip', 'bzip2'] as $compression) {
            foreach (['short content' => 'ab', 'missing padding' => 'abc', 'short padding' => 'abc' . str_repeat("\0", 508)] as $name => $payload) {
                foreach ([false, true] as $readContent) {
                    $cases[$compression . ' ' . $name . ' ' . ($readContent ? 'read' : 'skip')] = [$compression, $payload, $readContent];
                }
            }
        }
        return $cases;
    }

    private function assertInvalidArchive(string $archive): void
    {
        $stream = fopen('php://memory', 'wb+');
        try {
            fwrite($stream, $archive);
            rewind($stream);
            Assert::exception(function () use ($stream): void {
                foreach (new StreamReader($stream) as $file) {
                    $file->getContent()->getContents();
                }
            }, InvalidArchiveFormatException::class);
        } finally {
            fclose($stream);
        }
    }
}

(new StreamReaderTest())->run();
