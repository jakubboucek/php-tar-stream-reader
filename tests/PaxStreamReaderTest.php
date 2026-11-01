<?php

/** @testCase */

declare(strict_types=1);

namespace JakubBoucek\Tar\Tests;

use JakubBoucek\Tar\Exception\InvalidArchiveFormatException;
use JakubBoucek\Tar\Exception\NotImplementedException;
use JakubBoucek\Tar\StreamReader;
use Tester\Assert;
use Tester\TestCase;

use function is_resource;

require __DIR__ . '/bootstrap.php';

class PaxStreamReaderTest extends TestCase
{
    public function testIndependentFixtures(): void
    {
        $manifest = json_decode(
            file_get_contents(__DIR__ . '/assets/pax/manifest.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        foreach ($manifest as $name => $expected) {
            if ($name === 'global-size' || $name === 'global-path') {
                continue;
            }
            $archive = file_get_contents(__DIR__ . '/assets/pax/' . $name . '.tar');
            foreach (['plain', 'gzip', 'bzip2'] as $compression) {
                $this->withArchive($archive, function (StreamReader $reader) use ($expected): void {
                    $actual = [];
                    foreach ($reader as $file) {
                        $actual[] = [
                            'name' => $file->getName(),
                            'size' => $file->getSize(),
                            'sha256' => hash('sha256', (string) $file->getContent()),
                        ];
                    }
                    Assert::same($expected, $actual);
                }, $compression);
            }
            $this->withArchive($archive, function (StreamReader $reader) use ($expected): void {
                $actual = [];
                foreach ($reader as $file) {
                    $actual[] = [$file->getName(), $file->getSize()];
                }
                Assert::same(array_map(fn($entry) => [$entry['name'], $entry['size']], $expected), $actual);
            });
        }
    }

    public function testUnsupportedGlobalValues(): void
    {
        foreach (['global-size', 'global-path'] as $name) {
            $archive = file_get_contents(__DIR__ . '/assets/pax/' . $name . '.tar');
            $this->withArchive($archive, function (StreamReader $reader): void {
                Assert::exception(fn() => iterator_to_array($reader), NotImplementedException::class);
            });
        }
    }

    public function testOverridesPhysicalSizeAndPreservesExportPosition(): void
    {
        $archive = $this->sizeArchive();
        // The first real header follows one PAX header and its padded payload.
        $archive = $this->replaceHeaderSize($archive, 1024, 0);
        $this->withArchive($archive, function (StreamReader $reader): void {
            $target = fopen('php://temp', 'w+b');
            try {
                fwrite($target, 'prefix:');
                $names = [];
                foreach ($reader as $file) {
                    Assert::same(3, $file->getSize());
                    $names[] = $file->getName();
                    $file->getContent()->toStream($target);
                }
                rewind($target);
                Assert::same('prefix:abcxyz', stream_get_contents($target));
                Assert::same(['first.txt', 'last.txt'], $names);
            } finally {
                fclose($target);
            }
        });
    }

    public function testLocalMetadataDoesNotLeak(): void
    {
        $archive = $this->sizeArchive();
        // Remove the second PAX block and make the second entry an empty file.
        $archive = substr($archive, 0, 2048) . substr($archive, 3072, 512) . str_repeat("\0", 1024);
        $archive = $this->replaceHeaderSize($archive, 2048, 0);
        $this->withArchive($archive, function (StreamReader $reader): void {
            $sizes = [];
            foreach ($reader as $file) {
                $sizes[] = $file->getSize();
            }
            Assert::same([3, 0], $sizes);
        });
    }

    public function testTruncatedExtensionAndMissingFile(): void
    {
        $archive = $this->sizeArchive();
        $payloadSize = octdec(substr($archive, 124, 11));
        foreach ([
            substr($archive, 0, 511),
            substr($archive, 0, 512 + $payloadSize - 1),
            substr($archive, 0, 1023),
            substr($archive, 0, 1024),
            substr($archive, 0, 1024) . str_repeat("\0", 1024),
            $this->replaceHeaderSize(substr($archive, 0, 512), 0, 0),
        ] as $truncated) {
            $this->withArchive($truncated, function (StreamReader $reader): void {
                Assert::exception(fn() => iterator_to_array($reader), InvalidArchiveFormatException::class);
            });
        }
    }

    public function testOversizedExtension(): void
    {
        $archive = $this->replaceHeaderSize(substr($this->sizeArchive(), 0, 512), 0, 1048577);
        $this->withArchive($archive, function (StreamReader $reader): void {
            Assert::exception(fn() => iterator_to_array($reader), NotImplementedException::class);
        });
    }

    public function testContentLifetime(): void
    {
        $this->withArchive($this->sizeArchive(), function (StreamReader $reader): void {
            $iterator = $reader->getIterator();
            $first = $iterator->current()->getContent();
            Assert::same('a', $first->read(1));
            $iterator->next();
            Assert::same('abc', (string) $first);
            $unread = $iterator->current()->getContent();
            unset($iterator);
            Assert::true($unread->isClosed());
        });
    }

    private function sizeArchive(): string
    {
        return file_get_contents(__DIR__ . '/assets/pax/local-size.tar');
    }

    private function replaceHeaderSize(string $archive, int $offset, int $size): string
    {
        $header = substr($archive, $offset, 512);
        $header = substr_replace($header, sprintf('%011o', $size) . "\0", 124, 12);
        $header = substr_replace($header, '        ', 148, 8);
        $checksum = array_sum(unpack('C*', $header));
        $header = substr_replace($header, sprintf('%06o', $checksum) . "\0 ", 148, 8);
        return substr_replace($archive, $header, $offset, 512);
    }

    private function withArchive(string $archive, callable $callback, string $compression = 'plain'): void
    {
        $path = tempnam(sys_get_temp_dir(), 'pax-test-');
        $stream = null;
        try {
            if ($compression === 'gzip') {
                file_put_contents($path, gzencode($archive));
                $stream = gzopen($path, 'rb');
            } elseif ($compression === 'bzip2') {
                file_put_contents($path, bzcompress($archive));
                $stream = bzopen($path, 'r');
            } else {
                file_put_contents($path, $archive);
                $stream = fopen($path, 'rb');
            }
            $callback(new StreamReader($stream));
        } finally {
            if (is_resource($stream)) {
                if ($compression === 'gzip') {
                    gzclose($stream);
                } elseif ($compression === 'bzip2') {
                    bzclose($stream);
                } else {
                    fclose($stream);
                }
            }
            unlink($path);
        }
    }
}

new PaxStreamReaderTest()->run();
