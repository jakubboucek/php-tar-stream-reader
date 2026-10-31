<?php
/** @testCase */

declare(strict_types=1);

namespace JakubBoucek\Tar\Tests\Parser;

use JakubBoucek\Tar\Exception\FileContentClosedException;
use JakubBoucek\Tar\Exception\InvalidArgumentException;
use JakubBoucek\Tar\Exception\RuntimeException;
use JakubBoucek\Tar\Parser\LazyContent;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

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
        Assert::same(self::TestString, (string)$this->content);
    }

    public function testToStream(): void
    {
        $stream = fopen('php://memory', 'wb+');
        try {
            $this->content->toStream($stream);

            // test stream not rewinded
            Assert::same('', stream_get_contents($stream));

            // rewind
            Assert::same(0, fseek($stream, 0));

            // test stream has content
            Assert::same(self::TestString, stream_get_contents($stream));
        } finally {
            fclose($stream);
        }
    }

    public function testToStreamUsedContent(): void
    {
        $stream = fopen('php://memory', 'wb+');
        try {
            // Just fetch internal stream
            $this->content->tell();

            $this->content->toStream($stream);

            // test stream not rewinded
            Assert::same('', stream_get_contents($stream));

            // rewind
            Assert::same(0, fseek($stream, 0));

            // test stream has content
            Assert::same(self::TestString, stream_get_contents($stream));
        } finally {
            fclose($stream);
        }
    }

    public function testToStreamClosedContent(): void
    {
        $stream = fopen('php://memory', 'wb+');
        try {
            $this->content->close();

            Assert::exception(
                fn() => $this->content->toStream($stream),
                FileContentClosedException::class,
                "File's Content is already closed, try to fetch it right after File fetched from Reader.",
            );
        } finally {
            fclose($stream);
        }
    }

    public function testToStreamInvalid(): void
    {
        $stream = 'foo bar';

        Assert::exception(
            fn() => $this->content->toStream($stream),
            InvalidArgumentException::class,
            "Stream must be a resource",
        );
    }

    public function testToFileUsedContent(): void
    {
        $filename = tempnam(sys_get_temp_dir(), 'TestLazyContent');
        try {
            $this->content->tell();
            $this->content->toFile($filename);
            Assert::true(is_file($filename));
            Assert::same(self::TestString, file_get_contents($filename));
        } finally {
            unlink($filename);
        }
    }

    public function testToFileInvalid(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'TarMissing');
        unlink($path);
        $filename = $path . '/content.bin';

        Assert::error(
            fn() => Assert::exception(
                fn() => $this->content->toFile($filename),
                InvalidArgumentException::class,
                "Unable to open file: '$filename'",
            ),
            E_WARNING,
            '%a%Failed to open stream: No such file or directory',
        );
    }

    public function testIsLoaded(): void
    {
        Assert::false($this->content->isLoaded());

        // Just fetch internal stream
        $this->content->tell();

        Assert::true($this->content->isLoaded());

        // Just fetch internal stream
        $this->content->close();

        Assert::false($this->content->isLoaded());
    }

    public function testIsClosed(): void
    {
        Assert::false($this->content->isClosed());

        // Just fetch internal stream
        $this->content->tell();

        Assert::false($this->content->isClosed());

        // Just fetch internal stream
        $this->content->close();

        Assert::true($this->content->isClosed());
    }

    public function testClose(): void
    {
        Assert::false($this->content->isClosed());

        // Just fetch internal stream
        $this->content->close();

        Assert::true($this->content->isClosed());
    }

    public function testCloseUsed(): void
    {
        // Just fetch internal stream
        $this->content->tell();

        Assert::false($this->content->isClosed());

        // Just fetch internal stream
        $this->content->close();

        Assert::true($this->content->isClosed());
    }

    public function testDetach(): void
    {
        $stream = $this->content->detach();
        try {
            Assert::true($this->content->isClosed());

            Assert::same(self::TestString, stream_get_contents($stream));
        } finally {
            fclose($stream);
        }
    }

    public function testGetSize(): void
    {
        Assert::error(
            function (): void {
                Assert::null($this->content->getSize());
            },
            E_USER_WARNING,
            "Method 'JakubBoucek\\Tar\\Parser\\LazyContent::getSize' not implemented.",
        );
    }

    public function testTell(): void
    {
        Assert::same(0, $this->content->tell());

        $this->content->read(3);

        Assert::same(3, $this->content->tell());

        $this->content->read(1);

        // Still 3 - previous read cannot move pointer after EOF
        Assert::same(3, $this->content->tell());
    }

    public function testEof(): void
    {
        $this->content->getContents();

        Assert::true($this->content->eof());
    }

    public function testIsSeekable(): void
    {
        // Test stream as always seekable
        Assert::true($this->content->isSeekable());
    }

    public function testSeek(): void
    {
        // discharge the stream
        $this->content->getContents();
        // check the content is discharged
        Assert::same('', $this->content->getContents());

        $this->content->seek(0);

        Assert::same(self::TestString, $this->content->getContents());
    }

    public function testRewind(): void
    {
        // seek to eof
        Assert::same(self::TestString, $this->content->getContents());
        // test content seeked to end
        Assert::same('', $this->content->getContents());

        $this->content->rewind();

        // seek to eof
        Assert::same(self::TestString, $this->content->getContents());
    }

    public function testIsWritable(): void
    {
        Assert::false($this->content->isWritable());
    }

    public function testWrite(): void
    {
        Assert::exception(
            fn() => $this->content->write('bar'),
            RuntimeException::class,
            'Cannot write to a non-writable stream',
        );
    }

    public function testIsReadable(): void
    {
        Assert::true($this->content->isReadable());
    }

    public function testRead(): void
    {
        $len = strlen(self::TestString);

        Assert::same(self::TestString, $this->content->read($len));
    }

    public function testGetMetadata(): void
    {
        Assert::type('array', $this->content->getMetadata());
    }

    public function testGetMetadataItem(): void
    {
        Assert::same('MEMORY', $this->content->getMetadata('stream_type'));
    }

    public function testGetContents(): void
    {
        Assert::same(self::TestString, $this->content->getContents());
    }

    public function testGetStreamClosed(): void
    {
        $this->content->close();

        Assert::exception(
            fn() => $this->content->getContents(),
            FileContentClosedException::class,
            "File's Content is already closed, try to fetch it right after File fetched from Reader.",
        );
    }
}

(new LazyContentTest())->run();
