# Architecture and behavior

## Reader pipeline

`FileReader` chooses a handler from the case-sensitive filename suffix: `Gzip` matches `.gz` and `.tgz`; `Bz2` matches `.bz`, `.bz2`, `.tbz` and `.tbz2`; otherwise `Plain` is used. The optional second constructor argument overrides detection. Detection does not inspect file contents.

Each iteration of `FileReader` opens a resource and delegates to `StreamReader`. A `finally` block closes it after exhaustion, a reader exception or destruction of an unfinished generator (including a direct `foreach` interrupted by `break` or an exception). If the caller retains the generator, the resource remains open until that generator finishes or is released.

`StreamReader` accepts a resource containing decompressed TAR bytes. It does not rewind or close the caller's resource. Its generator reads 512-byte headers and yields `File` objects. Empty reads end iteration; all-zero blocks are skipped rather than treated as a mandatory two-block end marker. Short headers raise `InvalidArchiveFormatException`.

`File` is a readonly class: its header and content references cannot be reassigned, but the referenced content still has a mutable stream lifecycle. Subclasses must also be readonly. It delegates name, size and type access to `Header`. Regular file types are `0` and the NUL byte; directory type is `5`. Other type flags remain available through `getType()`. Casting a `File` to string returns its name, not its content.

## Content lifecycle

`File::getContent()` returns `LightStreamInterface`, an extension of PSR-7 `StreamInterface` with `toStream()` and `toFile()`. `LazyContent` starts with a closure referencing the current archive entry. `Usage` prevents that closure from consuming the archive twice.

- Accessing the content object alone does not read bytes. Most stream operations materialize the entire entry into `php://temp`, which can spill to disk. Reading a small chunk is therefore not a direct small read from the archive.
- Casting content to string rewinds the materialized stream and reads it all. `getContents()` reads from the current position. Both return strings whose size is limited by available memory.
- Calling `toStream()` before materialization copies directly from the archive into the caller's resource, without an intermediate content stream. The target is neither rewound nor closed. The content object becomes closed after successful export.
- Calling `toStream()` after materialization copies only the remaining bytes from the current position and closes the internal stream. Rewind first when a complete export is needed.
- `toFile()` rejects closed content before opening the destination. Otherwise it opens the supplied path in `wb` mode, delegates to `toStream()` and closes the target even when export throws. It does not create directories or provide atomic writes: an export failure after opening can leave an existing file truncated or partially written.
- `detach()` materializes content, rewinds a seekable internal stream, and transfers ownership to the caller. The caller must close the returned resource.
- Advancing the archive iterator closes unconsumed content and skips its bytes and padding. Destroying an unfinished generator also closes unconsumed content, without reading the remaining bytes. Retaining a `File` does not retain unread archive content. Already materialized content remains usable until closed, exported, detached or destroyed.
- `getMetadata()` materializes content and returns metadata of its internal PHP stream, not TAR header fields. Its `mixed` return type represents both the full metadata array and individual values. Only a null key requests the whole array; other keys return the matching value or `null` when absent, including empty-string and `"0"` keys.
- `LazyContent::getSize()` emits a user warning and returns `null`; use `File::getSize()` for the declared entry size. Content is read-only; `write()` throws.

The archive cursor advances by entry size plus padding to a 512-byte boundary. Content copies check the number of transferred bytes. Unused content and padding are read and discarded in chunks of at most 8192 bytes; missing bytes raise `InvalidArchiveFormatException` even during metadata-only traversal. This keeps memory bounded but requires reading skipped data rather than seeking over it. Arbitrary non-seekable resources are not guaranteed to work.

## Format support and limitations

- Header validation checks only the `ustar` magic. Checksums are not validated. Size is parsed only as octal; PAX size overrides and GNU base-256 sizes are not implemented.
- Names come only from the first 100 header bytes. The USTAR prefix, PAX `path` and GNU long-name records are not applied. Link targets and filesystem metadata are not exposed through `File`.
- PAX metadata is not parsed or applied. Entries with local type `x` or global type `g` are yielded like other entries when their headers pass validation; they do not modify subsequent entries.

There is no archive-writing API or automatic filesystem extraction. Export paths are supplied by the caller; entry names are raw metadata and should not be used as trusted destination paths without validation.

## Errors

Library `InvalidArchiveFormatException` extends the library `RuntimeException`. Library `InvalidArgumentException` extends the library `LogicException`. `FileContentClosedException` directly extends PHP's `LogicException`, so catching the library `LogicException` does not catch it. `EofException` is an internal control signal caught by the reader. Underlying PHP stream operations can also emit warnings; do not assume every failure is normalized into a library exception.

`LazyContent::read()` wraps any `Throwable` from materialization or `fread()` in the library `RuntimeException`, preserving the original throwable as `getPrevious()`. Native warnings without a throwing error handler are not converted by this catch.
