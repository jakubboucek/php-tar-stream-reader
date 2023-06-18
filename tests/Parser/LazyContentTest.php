<?php

declare(strict_types=1);

namespace JakubBoucek\Tar\Tests\Parser;

use JakubBoucek\Tar\Exception\FileContentClosedException;
use JakubBoucek\Tar\Exception\InvalidArgumentException;
use JakubBoucek\Tar\Exception\RuntimeException;
use JakubBoucek\Tar\Parser\LazyContent;
use JakubBoucek\Tar\Tests\Exception\UnexpectedError;
use PHPUnit\Framework\TestCase;

class LazyContentTest extends TestCase
{
    private const TestString = 'foo';

    private LazyContent $content;

    public function setUp(): void
    {
        /**
         * @param resource|null $external
         * @return resource
         */
        $closure = static function ($external = null) {
            $stream = $external ?? fopen('php://memory', 'wb+');
            fwrite($stream, self::TestString);
            if (!$external) {
                fseek($stream, 0);
            }
            return $stream;
        };

        $this->content = new LazyContent($closure);
    }

    protected function tearDown(): void
    {
        unset($this->content);
    }


    public function test__toString(): void
    {
        $this->assertEquals(self::TestString, (string)$this->content);
    }

    public function testToStream(): void
    {
        $stream = fopen('php://memory', 'wb+');

        $this->content->toStream($stream);

        // test stream not rewinded
        $this->assertEmpty(stream_get_contents($stream));

        // rewind
        $this->assertEquals(0, fseek($stream, 0));

        // test stream has content
        $this->assertEquals(self::TestString, stream_get_contents($stream));
    }

    public function testToStreamUsedContent(): void
    {
        $stream = fopen('php://memory', 'wb+');

        // Just fetch internal stream
        $this->content->tell();

        $this->content->toStream($stream);

        // test stream not rewinded
        $this->assertEmpty(stream_get_contents($stream));

        // rewind
        $this->assertEquals(0, fseek($stream, 0));

        // test stream has content
        $this->assertEquals(self::TestString, stream_get_contents($stream));
    }

    public function testToStreamClosedContent(): void
    {
        $this->expectException(FileContentClosedException::class);
        $this->expectExceptionMessage(
            "File's Content is already closed, try to fetch it right after File fetched from Reader."
        );

        $stream = fopen('php://memory', 'wb+');

        $this->content->close();

        $this->content->toStream($stream);
    }

    public function testToStreamInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Stream must be a resource");

        $stream = 'foo bar';

        /** @noinspection PhpParamsInspection test case */
        $this->content->toStream($stream);
    }

    public function testToFileUsedContent(): void
    {
        $tmpDir = sys_get_temp_dir();
        $tmpNam = tempnam($tmpDir, 'TestLazyContent');

        // Just fetch internal stream
        $this->content->tell();

        $this->content->toFile($tmpNam);

        $this->assertFileExists($tmpNam);
        $this->assertEquals(self::TestString, file_get_contents($tmpNam));
        unlink($tmpNam);
    }

    public function testToFileInvalid(): void
    {
        // Expect warning
        $prev = set_error_handler(static function (int $errno, string $errstr) use (&$prev): bool {
            if (!str_contains($errstr, "Failed to open stream: No such file or directory")) {
                throw new UnexpectedError($errstr, $errno);
            }
            return true;
        }, E_WARNING);

        $tmpDir = sys_get_temp_dir() . '/__@$non-exists';
        $tmpNam = sprintf("%s/file_%s.tmp", $tmpDir, random_int(1000, 9999));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Unable to open file: \'.+\\/__@\\$non-exists\\/file_\\d+\.tmp\'/');
        $this->content->toFile($tmpNam);
    }

    public function testIsLoaded(): void
    {
        $this->assertFalse($this->content->isLoaded());

        // Just fetch internal stream
        $this->content->tell();

        $this->assertTrue($this->content->isLoaded());

        // Just fetch internal stream
        $this->content->close();

        $this->assertFalse($this->content->isLoaded());
    }

    public function testIsClosed(): void
    {
        $this->assertFalse($this->content->isClosed());

        // Just fetch internal stream
        $this->content->tell();

        $this->assertFalse($this->content->isClosed());

        // Just fetch internal stream
        $this->content->close();

        $this->assertTrue($this->content->isClosed());
    }


    public function testClose(): void
    {
        $this->assertFalse($this->content->isClosed());

        // Just fetch internal stream
        $this->content->close();

        $this->assertTrue($this->content->isClosed());
    }

    public function testCloseUsed(): void
    {
        // Just fetch internal stream
        $this->content->tell();

        $this->assertFalse($this->content->isClosed());

        // Just fetch internal stream
        $this->content->close();

        $this->assertTrue($this->content->isClosed());
    }

    public function testDetach(): void
    {
        $stream = $this->content->detach();

        $this->assertTrue($this->content->isClosed());

        $this->assertEquals(self::TestString, stream_get_contents($stream));

        fclose($stream);
    }

    public function testGetSize(): void
    {
        // Expect warning
        set_error_handler(static function (int $errno, string $errstr): bool {
            if (!preg_match("/^Method \'.+\' not implemented\.$/D", $errstr)) {
                throw new UnexpectedError($errstr, $errno);
            }
            return true;
        }, E_USER_WARNING);

        $this->assertNull($this->content->getSize());
    }

    public function testTell(): void
    {
        $this->assertEquals(0, $this->content->tell());

        $this->content->read(3);

        $this->assertEquals(3, $this->content->tell());

        $this->content->read(1);

        // Still 3 - previous read cannot move pointer after EOF
        $this->assertEquals(3, $this->content->tell());
    }

    public function testEof(): void
    {
        $this->content->getContents();

        $this->assertTrue($this->content->eof());
    }

    public function testIsSeekable(): void
    {
        // Test stream as always seekable
        $this->assertTrue($this->content->isSeekable());
    }

    public function testSeek(): void
    {
        // discharge the stream
        $this->content->getContents();
        // check the content is discharged
        $this->assertEmpty($this->content->getContents());

        $this->content->seek(0);

        $this->assertEquals(self::TestString, $this->content->getContents());
    }

    public function testRewind(): void
    {
        // seek to eof
        $this->assertEquals(self::TestString, $this->content->getContents());
        // test content seeked to end
        $this->assertEmpty($this->content->getContents());

        $this->content->rewind();

        // seek to eof
        $this->assertEquals(self::TestString, $this->content->getContents());
    }

    public function testIsWritable(): void
    {
        $this->assertFalse($this->content->isWritable());
    }

    public function testWrite(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot write to a non-writable stream');

        $this->content->write('bar');
    }

    public function testIsReadable(): void
    {
        $this->assertTrue($this->content->isReadable());
    }

    public function testRead(): void
    {
        $len = strlen(self::TestString);

        $this->assertEquals(self::TestString, $this->content->read($len));
    }

    public function testGetMetadata(): void
    {
        $this->assertIsArray($this->content->getMetadata());
    }

    public function testGetMetadataItem(): void
    {
        $this->assertEquals('MEMORY', $this->content->getMetadata('stream_type'));
    }

    public function testGetContents(): void
    {
        $this->assertEquals(self::TestString, $this->content->getContents());
    }

    public function testGetStreamClosed(): void
    {
        $this->expectException(FileContentClosedException::class);
        $this->expectExceptionMessage(
            "File's Content is already closed, try to fetch it right after File fetched from Reader."
        );

        $this->content->close();
        $this->content->getContents();
    }
}
