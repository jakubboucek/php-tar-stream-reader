# Architecture and behavior

## Reader pipeline

`FileReader` chooses a handler from the case-sensitive filename suffix: `Gzip` matches `.gz` and `.tgz`; `Bz2` matches `.bz`, `.bz2`, `.tbz` and `.tbz2`; otherwise `Plain` is used. The optional second constructor argument overrides detection. Detection does not inspect file contents.

Each iteration of `FileReader` opens a resource and delegates to `StreamReader`. The handler closes it after normal exhaustion. This cleanup is not protected by `finally`, so early termination and exceptions do not guarantee that the explicit close call runs.

`StreamReader` accepts a resource containing decompressed TAR bytes. It does not rewind or close the caller's resource. Its generator reads 512-byte headers and yields `File` objects. Empty reads end iteration; all-zero blocks are skipped rather than treated as a mandatory two-block end marker. Short headers raise `InvalidArchiveFormatException`.

`File` delegates name, size and type access to `Header`. Regular file types are `0` and the NUL byte; directory type is `5`. Other type flags remain available through `getType()`. Casting a `File` to string returns its name, not its content.

## Content lifecycle

`File::getContent()` returns `LightStreamInterface`, an extension of PSR-7 `StreamInterface` with `toStream()` and `toFile()`. `LazyContent` starts with a closure referencing the current archive entry. `Usage` prevents that closure from consuming the archive twice.

- Accessing the content object alone does not read bytes. Most stream operations materialize the entire entry into `php://temp`, which can spill to disk. Reading a small chunk is therefore not a direct small read from the archive.
- Casting content to string rewinds the materialized stream and reads it all. `getContents()` reads from the current position. Both return strings whose size is limited by available memory.
- Calling `toStream()` before materialization copies directly from the archive into the caller's resource, without an intermediate content stream. The target is neither rewound nor closed. The content object becomes closed after successful export.
- Calling `toStream()` after materialization copies only the remaining bytes from the current position and closes the internal stream. Rewind first when a complete export is needed.
- `toFile()` opens the supplied path in `wb` mode, delegates to `toStream()` and closes the target on success. It does not create directories or protect an existing file from truncation. Exception cleanup is not protected by `finally`.
- `detach()` materializes content, rewinds a seekable internal stream, and transfers ownership to the caller. The caller must close the returned resource.
- Advancing the archive iterator closes unconsumed content and skips its bytes and padding. Retaining a `File` does not retain unread archive content. Already materialized content remains usable until closed, exported, detached or destroyed.
- `LazyContent::getSize()` emits a user warning and returns `null`; use `File::getSize()` for the declared entry size. Content is read-only; `write()` throws.

The archive cursor advances by entry size plus padding to a 512-byte boundary. Content copies check the number of transferred bytes. Skipping unused content and padding uses forward `fseek()`, so arbitrary non-seekable resources are not generally supported. A successful seek alone does not prove that skipped bytes exist.

## Format boundaries and areas requiring care

These are current implementation limits, not guarantees to preserve when fixing a bug:

- Header validation checks only the `ustar` magic. Checksums are not validated. Size is parsed only as octal; PAX size overrides and GNU base-256 sizes are not implemented.
- Names come only from the first 100 header bytes. The USTAR prefix, PAX `path` and GNU long-name records are not applied. Link targets and filesystem metadata are not exposed through `File`.
- PAX metadata is not parsed or applied. Entries with local type `x` or global type `g` are yielded like other entries when their headers pass validation; they do not modify subsequent entries.
- Handler tests check opening compressed resources, not complete bzip2 archive traversal. Forward seeking through a compression resource needs separate verification.

There is no archive-writing API or automatic filesystem extraction. Export paths are supplied by the caller; entry names are raw metadata and should not be used as trusted destination paths without validation.

## Errors

Library `InvalidArchiveFormatException` extends the library `RuntimeException`. Library `InvalidArgumentException` extends the library `LogicException`. `FileContentClosedException` directly extends PHP's `LogicException`, so catching the library `LogicException` does not catch it. `EofException` is an internal control signal caught by the reader. Underlying PHP stream operations can also emit warnings; do not assume every failure is normalized into a library exception.
