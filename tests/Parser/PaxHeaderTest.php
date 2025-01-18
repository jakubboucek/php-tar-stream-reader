<?php

namespace JakubBoucek\Tar\Tests\Parser;

use Generator;
use JakubBoucek\Tar\StreamReader;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../ResourceGenerators/MassiveFileStreamWrapper.php';

class PaxHeaderTest extends TestCase
{
    const MAX_USTAR_SIZE = 8589934591; // 8GB - 1

    protected function setUp(): void
    {
        if (getenv('CI') != 'true' && getenv('TEST_MASSIVE_FILE') != 'true') {
            $this->markTestSkipped('Massive file test is skipped. Set TEST_MASSIVE_FILE=true to run it.');
        }
        parent::setUp();
    }

    protected function generateSingleFile(?int $chunkSize = null, ?int $totalSize = null): Generator
    {
        $chunkSize ??= 1024 * 1024; // 1MB chunks
        $totalSize ??= 10 * 1024 * $chunkSize; // 10GB
        assert($totalSize > $chunkSize);
        $chunks = floor($totalSize / $chunkSize);
        $extra = $totalSize % $chunkSize;

        if ($totalSize > self::MAX_USTAR_SIZE) {
            // Generate a pax header and data record for a file larger than 8GB
            $data = implode("\n", [
                    implode(' ', array_reverse([$data = 'size=' . $totalSize, strlen($data)])),
                ]) . "\n";
            $dataLength = strlen($data);
            $data = str_pad($data, 512 * ceil($dataLength / 512), "\0");

            yield str_pad(implode('', [
                /* 0 */ 'name' => str_pad('massive_file.txt', 100, "\0"),
                /*100*/ 'mode' => str_pad('', 8, "\0"),
                /*108*/ 'uid' => str_pad('', 8, "\0"),
                /*116*/ 'gid' => str_pad('', 8, "\0"),
                /*100*/ 'size' => str_pad(decoct($dataLength), 12, "\0"),
                /*136*/ 'mtime' => str_pad('', 12, "\0"),
                /*148*/ 'chksum' => str_pad('', 8, "\0"),
                /*156*/ 'typeflag' => 'x',
                /*157*/ 'linkname' => str_pad('', 100, "\0"),
                /*257*/ 'magic' => "ustar\0",
                /*263*/ 'version' => str_pad('', 2, "\0"),
                /*265*/ 'uname' => str_pad('', 32, "\0"),
                /*297*/ 'gname' => str_pad('', 32, "\0"),
                /*329*/ 'devmajor' => str_pad('', 8, "\0"),
                /*337*/ 'devminor' => str_pad('', 8, "\0"),
                /*345*/ 'prefix' => str_pad('PaxHeaders.0', 155, "\0"),
            ]), 512, "\0");
            yield $data;
        }

        yield str_pad(implode('', [
            /* 0 */ 'name' => str_pad('massive_file.txt', 100, "\0"),
            /*100*/ 'mode' => str_pad('', 8, "\0"),
            /*108*/ 'uid' => str_pad('', 8, "\0"),
            /*116*/ 'gid' => str_pad('', 8, "\0"),
            /*100*/ 'size' => str_pad(decoct(min(octdec('77777777777'), $totalSize)), 12, "\0"),
            /*136*/ 'mtime' => str_pad('', 12, "\0"),
            /*148*/ 'chksum' => str_pad('', 8, "\0"),
            /*156*/ 'typeflag' => '0',
            /*157*/ 'linkname' => str_pad('', 100, "\0"),
            /*257*/ 'magic' => "ustar\0",
            /*263*/ 'version' => str_pad('', 2, "\0"),
            /*265*/ 'uname' => str_pad('', 32, "\0"),
            /*297*/ 'gname' => str_pad('', 32, "\0"),
            /*329*/ 'devmajor' => str_pad('', 8, "\0"),
            /*337*/ 'devminor' => str_pad('', 8, "\0"),
            /*345*/ 'prefix' => str_pad('', 155, "\0"),
        ]), 512, "\0");

        $alphabet = range('A', 'Z');
        for ($i = 0; $i < $chunks; $i++) {
            yield str_repeat($alphabet[$i % 26], $chunkSize);
        }

        if ($extra > 0) {
            yield str_repeat('#', $extra);
        }
    }

    public function fileCountProvider()
    {
        return [
            'one' => [1],
            'two' => [2],
            'three' => [3],
        ];
    }

    /**
     * @dataProvider fileCountProvider
     */
    public function testPaxHeader(int $fileCount): void
    {
        $fileGenerator = (function () use ($fileCount) {
            for ($i = 0; $i < $fileCount; $i++) {
                yield from $this->generateSingleFile();
            }
        })();
        $resource = fopen('massive-file://', 'r', context: stream_context_create([
            'massive-file' => ['generator' => $fileGenerator],
        ]));
        $this->assertIsResource($resource);

        try {
            $streamReader = new StreamReader($resource);

            $iterator = $streamReader->getIterator();
            $count = 0;
            foreach ($iterator as $file) {
                $count++;
                $fileResource = $file->getContent()->detach();
                $bytesRead = 0;
                while (!feof($fileResource)) {
                    $bytesRead += strlen(fread($fileResource, 1024 ** 2));
                }
                $this->assertSame($file->getSize(), $bytesRead);
            }
            $this->assertSame($fileCount, $count);
        } finally {
            fclose($resource);
        }
    }
}
