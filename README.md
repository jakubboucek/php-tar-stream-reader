# TAR Archive stream-based reader

Reader for TAR and TAR+GZip Archives, optimized for read huge size archives, effective memory usage.

## Features

- Read **TAR archives** from disk
- Support **GZipped** and **BZipped** archives
- Iterate over Archive content
- Get **name**, **size** and **type** of each file
- Apply local PAX names and sizes, including entries larger than the USTAR size limit
- Recognize Directory files type
- Recognize Regular files type
- Get **content** of files
- Allows to export content to files
- Optimized for performance and low-memory
- Read content lazily and export directly to a destination stream

## Install

Requires PHP 8.4 or later within PHP 8.x and PSR HTTP Message 2.x. Tested on PHP 8.4–8.6. Gzip and bzip2 support need `ext-zlib` and `ext-bz2`, respectively.

```shell
composer require jakubboucek/tar-stream-reader
```

## Usage 

Read files from an archive:
```php
use JakubBoucek\Tar;

foreach (new Tar\FileReader('example.tar') as $file) {
    echo "File {$file->getName()} is {$file->getSize()} bytes size, content of file:\n";
    echo $file->getContent() . "\n";
}
```

Package recognizes few types of Archive when using classic filename extension (e.g.: `.tar`, `.tgz`, `.tar.bz2`), but
You can explicitly define archive type thought second parameter:
```php
use JakubBoucek\Tar;

foreach (new Tar\FileReader('example.tar+gzip', new Tar\FileHandler\Gzip()) as $file) {
    echo "File {$file->getName()} is {$file->getSize()} bytes size.\n";
}
```

For an existing resource containing decompressed TAR bytes, use `StreamReader` instead of `FileReader`:
```php
use JakubBoucek\Tar;

$stream = someBeatifulFuntionToGetStream();

foreach (new Tar\StreamReader($stream) as $file) {
    echo "File {$file->getName()} is {$file->getSize()} bytes size.\n";
}
```

### Content lifetime and format limits

- Consume or materialize content before advancing the archive iterator. Unread content is closed when the iterator advances; retaining a `File` object does not retain its unread bytes.
- For large entries, call `toStream()` or `toFile()` before any content reads to export directly. Other stream operations can materialize the entire entry into temporary storage; casting content to a string loads it into PHP memory.
- Export consumes the content object. If content is already materialized, export starts at its current position; rewind it first to export the whole entry.
- Skipped content and padding are read and discarded in bounded chunks so truncated entries are detected even during metadata-only traversal. Arbitrary non-seekable streams are not guaranteed to work.
- Local PAX `path` and `size` override entry metadata. PAX headers are consumed internally. Global `path`/`size`, deletion of local `path`/`size`, and PAX payloads larger than 1 MiB raise `Exception\NotImplementedException`. Other PAX metadata is ignored; names retain their original bytes without charset conversion.
- TAR support is limited: USTAR name prefixes, GNU long-name records and GNU sparse formats are not implemented, and header checksums are not validated. Archive entry names are untrusted metadata; validate them before using them as export paths.

## FAQ

### Can I use Package for ZIP, RAR, … archives? 

No, Package recognize only TAR Archive format, additionaly recognize GZipped or BZipped Archive.

### Can I use Package to create/modify Archive?

No, Package provide only read possibility.

### Can I search file in Archive?

No, TAR Archive is stream-based format, it does not support search, you must always iterate over whole Archive.

### How big file I can proceed?

Here are two scopes of this question: **Archive size** or **Size of files in Archive**

- **Archive size:** sequential reading avoids loading the whole archive into memory.
- **Entry size:** direct export through `toFile()` or `toStream()` avoids creating a whole-entry PHP string. Actual limits still depend on supported TAR size encoding, the PHP runtime and available storage. Local PAX decimal sizes are supported up to `PHP_INT_MAX`; GNU base-256 sizes are not supported.

## API overview

All public classes use the `JakubBoucek\Tar` namespace:

| Class or interface | Purpose |
| --- | --- |
| `FileReader` | Iterate over an archive by filename, with automatic compression handler selection or an explicit handler. |
| `StreamReader` | Iterate over an open resource containing decompressed TAR bytes. The caller owns and closes the resource. |
| `File` | Read entry metadata through `getName()`, `getSize()`, `getTypeEnum()`, `isFile()` and `isDir()`. `getType()` preserves the original flag byte. |
| `EntryType` | Recognized TAR types returned by `getTypeEnum()`; unknown flags return `Unknown`; the legacy NUL flag maps to `ARegularFile`. Both regular-file cases satisfy `EntryType::isFile()`. |
| `Parser\LightStreamInterface` | Access entry content through `File::getContent()`. Extends PSR-7 `StreamInterface` with `toStream()` and `toFile()`. |

## Documentation and source

Detailed documentation is available in the [GitHub repository](https://github.com/jakubboucek/php-tar-stream-reader):

- [Architecture, content lifecycle and format limitations](https://github.com/jakubboucek/php-tar-stream-reader/blob/master/docs/architecture.md)
- [Development and testing](https://github.com/jakubboucek/php-tar-stream-reader/blob/master/docs/development.md)
- [Contributor and agent instructions](https://github.com/jakubboucek/php-tar-stream-reader/blob/master/AGENTS.md)

These links point to the maintained `master` branch. When investigating an installed package, use its matching release tag or the Composer source reference recorded in the consuming project's `composer.lock`. The installed source code is authoritative for that version. Detailed documentation and development tooling are intentionally omitted from distribution archives.

## Contributing
Please don't hesitate send Issue or Pull Request.

Read the documentation linked above before making changes.

## Security
If you discover any security related issues, please email pan@jakubboucek.cz instead of using the issue tracker.

## License
The MIT License (MIT). Please see [License File](LICENSE) for more information.
