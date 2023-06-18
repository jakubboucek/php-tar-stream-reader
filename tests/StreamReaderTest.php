<?php

declare(strict_types=1);

namespace JakubBoucek\Tar\Tests;

use JakubBoucek\Tar\File;
use JakubBoucek\Tar\StreamReader;
use PHPUnit\Framework\TestCase;

class StreamReaderTest extends TestCase
{
    public function testTraverseHeads(): void
    {
        $stream = gzopen(__DIR__ . '/assets/test-archive-null.tgz', 'rb');
        $patterns = json_decode(
            file_get_contents(__DIR__ . '/assets/test-archive-null.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $iterator = (new StreamReader($stream))->getIterator();

        foreach ($patterns as $pattern) {
            /** @noinspection DisconnectedForeachInstructionInspection */
            $this->assertTrue($iterator->valid());

            /** @var File $file */
            $file = $iterator->current();
            $this->assertEquals($pattern['file'], $file->getName());
            $this->assertEquals($pattern['length'], $file->getSize());

            /** @noinspection DisconnectedForeachInstructionInspection */
            $iterator->next();
        }
    }

    public function testTraverseContents(): void
    {
        $stream = gzopen(__DIR__ . '/assets/test-archive-random.tgz', 'rb');
        $patterns = json_decode(
            file_get_contents(__DIR__ . '/assets/test-archive-random.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $iterator = (new StreamReader($stream))->getIterator();

        foreach ($patterns as $pattern) {
            /** @noinspection DisconnectedForeachInstructionInspection */
            $this->assertTrue($iterator->valid());

            /** @var File $file */
            $file = $iterator->current();
            $this->assertEquals($pattern['hash'], md5((string)$file->getContent()));

            /** @noinspection DisconnectedForeachInstructionInspection */
            $iterator->next();
        }
    }
}
