# TAR Archive stream-based reader

Reader for TAR and TAR+GZip Archives, optimized for read huge size archives, effective memory usage.

## Features

- Read **TAR archives** from disk
- Support **GZipped** and **BZipped** archives
- Iterate over Archive content
- Get **name**, **size** and **type** of each file
- Recognize Directory files type
- Recognize Regular files type
- Get **content** of files
- Allows to export content to files
- Optimized for performance and low-memory
- Use stream-first access - file content not stored to memory

## Install

```shell
composer require jakubboucek/tar-stream-reader
```

## Usage 

Read files from an archive:
```php
use JakubBoucek\Tar;

foreach (new Tar\FileReader('example.tar') as $file) {
    echo "File {$file->getName()} is {$file->getSize()} bytes size, content of file:\n";
    echo $fileInfo->getContent() . "\n";
}
```

Package recognizes few types of Archive when using classic filename extension (e.g.: `.tar`, `.tgz`, `.tar.bz2`), but
You can explicitly define archive type thought second parameter:
```php
use JakubBoucek\Tar;

foreach (new Tar\FileReader('example.tar+gzip', new Tar\Filehandler\Gzip()) as $file) {
    echo "File {$file->getName()} is {$file->getSize()} bytes size.\n";
}
```

Package allows to process any type of stream, use `StreamReader` instead of `FileReader`:
```php
use JakubBoucek\Tar;

$stream = someBeatifulFuntionToGetStream();

foreach (new Tar\StreamReader($stream) as $file) {
    echo "File {$file->getName()} is {$file->getSize()} bytes size.\n";
}
```

## FAQ

### Can I use Package for ZIP, RAR, … archives? 

No, Package recognize only TAR Archive format, additionaly recognize GZipped or BZipped Archive.

### Can I use Package to create/modify Archive?

No, Package provide only read possibility.

### Can I search file in Archive?

No, TAR Archive is stream-based format, it does not support search, you must always iterate over whole Archive.

### How big file I can proceed?

Here are two scopes of this question: **Archive size** or **Size of files in Archive**

- **Archive size** is teoretically unlimited, beacuse package is using stream very effective.  
- **Size of files in Archive** is teoretically unlimited when use steam-based method to extraxt content
(`toFile()` or `toStream()`), otherwise is size limited with available memory, because is content filled into variable.  

## Contributing
Please don't hesitate send Issue or Pull Request.

## Security
If you discover any security related issues, please email pan@jakubboucek.cz instead of using the issue tracker.

## License
The MIT License (MIT). Please see [License File](LICENSE) for more information.
