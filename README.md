# TAR Archive stream-based reader

Reader for TAR and TAR+GZip Archives, optimized for read huge size archives, effective memory usage.

## Features

- Read **TAR archives** from disk
- Support **GZipped archives**
- Iterate over Archive content
- Get **name**, **size** and **type** of each file
- Recognize Directory files type
- Recognize Regular files type
- Get **content** of files
- Scan file list only mode (doesn't read file's content from disk)

## Install

```shell
composer require jakubboucek/tar-stream-reader
```

## Usage 

Read files from an archive:
```php
foreach (ArchiveReader::read('example.tar') as $filename => $fileInfo) {
    echo "File {$filename} is {$fileInfo->getSize()} bytes size, content of file:\n";
    echo $fileInfo->getContent() . "\n\n";
}
```

Only scan files from an archive:
```php
foreach (ArchiveReader::scan('example.tar') as $filename => $fileInfo) {
    echo "File {$filename} is {$fileInfo->getSize()} bytes size, content of file.\n";
}
```
Scan mode is skipping contents od files in Archive. It's faster and less memory consume than read mode. 

Define type of archive:
```php
foreach (ArchiveReader::scan('example.tar+gzip', ArchiveReader::TYPE_GZ) as $filename => $fileInfo) {
    echo "File {$filename} is {$fileInfo->getSize()} bytes size, content of file.\n";
}
```
Package recognize right type of Archive when using classic filename extension (`.tar`, `.tgz`, `.tar.gz`).

## FAQ

### Can I use Package for ZIP, RAR, BZ, … archives? 

No, Package recognize only TAR Archive format, additionaly recognize GZipped Archive.

### Can I use Package to create/modify Archive?

No, Package provide only read possibility.

### Can I search file in Archive?

No, TAR Archive is stream-based format, it does not support search, you must always iterate over whole Archive.

### How big file I can proceed?

Here are two scopes of this question: **Archive size** or **Size of files in Archive**

- **Archive size** is teoretically unlimited, beacuse package is using very effective  
- **Size of files in Archive** is in read mode limited to available RAM because Content of each file is directly
loaded to variable. For each file content is not abailable another read method.

## Contributing
Please don't hesitate send Issue or Pull Request.

## Security
If you discover any security related issues, please email pan@jakubboucek.cz instead of using the issue tracker.

## License
The MIT License (MIT). Please see [License File](LICENSE) for more information.