# PAX implementation requirements

PAX metadata is currently unsupported. This document records constraints for a future implementation; it does not describe an available API.

## References

- [POSIX pax format](https://pubs.opengroup.org/onlinepubs/9699919799/utilities/pax.html), especially "pax Header Block", "pax Extended Header" and "pax Extended Header Keyword Precedence".
- [Contributor proposal, PR #5](https://github.com/jakubboucek/php-tar-stream-reader/pull/5), head `74224d3c157d19ec33344c02916313da89933cef`.
- [Contributor fork](https://github.com/piurafunk/php-tar-stream-reader/tree/4c3d20f66aad2e037412ca30465b6b022c10e3cf).

## Record decoding

- Parse `<length> <keyword>=<value>\n` by the declared byte length, including its decimal prefix, space and final newline. Values may contain newlines and equals signs. Splitting the payload into lines can turn value bytes into false metadata records.
- Validate framing, decimal lengths, complete reads and the final newline. Skip unsupported keywords by their record boundaries, without interpreting their values as additional records.
- Validate `size` as a nonnegative decimal integer before conversion, including overflow against the supported integer range. An empty value is a deletion instruction, not numeric zero.
- Keep absent, set and deleted values distinct. Apply the POSIX deletion and precedence rules explicitly; do not accidentally restore an overridden USTAR field or treat deletion as an ordinary empty path.
- Decide and document encoding behavior, including UTF-8 and `hdrcharset=BINARY`, before claiming path support. Raw archive metadata must not silently undergo lossy conversion.

## Archive integration

- An `x` header applies to the following file. Decode local `size` and `path`; later duplicate records take precedence within the header.
- The initial implementation will inspect global `g` records and reject any occurrence of `size` or `path` with a dedicated not-implemented exception, including empty values and values overridden locally. Other global records can be skipped, subject to the explicit filename encoding policy. Global size and path are valid format features; rejection is a deliberate support boundary, not a malformed-archive diagnosis.
- Read extension payloads using the physical extension header's size. Resolve the following file's effective size before calculating its content boundary and padding.
- Padding is `(512 - (size % 512)) % 512`; exact multiples of 512 require no padding.
- Validate skipped bytes by reading them, as the current reader does. Seeking past EOF does not establish that bytes existed. Distinguish an incomplete extension or missing following file from normal archive completion.
- Use an iterative metadata-processing loop with explicit next-entry state rather than recursively reading headers. No global size/path state is needed under the rejection policy.
- Preserve bounded memory use. Do not allocate an arbitrary declared extension size in one `fread()`; decode incrementally or define an explicit resource limit with a clear exception.
- Separate raw TAR headers, PAX record decoding and effective entry metadata. Preserve the current lazy content and export lifecycle.
- Treat direct resource access as a separate API design problem. It requires ownership, invalidation, buffering, EOF and seeking rules; it is not required to support PAX sizes.

## Verification cases

Use focused Nette Tester tests with small archives and independently generated fixtures:

- Record and payload boundaries, including 511, 512, 513 and 1024 bytes; byte lengths across decimal digit boundaries and multibyte values.
- Newlines and equals signs in values, unsupported keywords, malformed lengths, truncated payloads and padding, and missing following headers.
- Decimal size zero, the USTAR limit (8589934591), values above that limit, integer overflow and invalid numeric input.
- Local scope, duplicate records and empty-value deletion; accepted irrelevant global records and rejected global `size`/`path`, including empty values.
- PAX paths, ordinary USTAR fallback, compressed archives and multiple entries after an overridden size.
- Content reads, direct export, skipped content, early termination and already-materialized content retained after iterator advancement.

Large-size parsing can be tested without transferring gigabytes. Keep any full large-file transfer test separate from the fast format tests. The contributor's original large-file generator omits the length prefix, separator and newline from the record length; do not reuse it as a format oracle.

## Generating independent fixtures

Use GNU tar `--format=posix` or BSD tar `--format=pax`. A long single filename component forces a PAX `path` record. Merely selecting the format does not ensure that a particular keyword is emitted.

GNU tar can force selected records for small files. With a three-byte `file.txt`:

```sh
tar --format=posix --pax-option=size:=3 -cf local-size.tar file.txt
tar --format=posix --pax-option=comment=fixture -cf global-comment.tar file.txt
tar --format=posix --pax-option=size=3 -cf global-size.tar file.txt
tar --format=posix --pax-option=path=renamed.txt -cf global-path.tar file.txt
```

Here `:=` produces local records and `=` produces global records. Keep forced sizes equal to actual content lengths. Verify physical header types and records, then cross-read fixtures with an independent implementation such as Python `tarfile` or libarchive. Record generator commands and tool versions alongside committed fixtures. Generate malformed-input tests separately by controlled mutations of valid archives.

## Deferred integration idea

Nice to have: an opt-in, manually invoked Docker Engine archive test with a single file larger than 8 GiB minus one byte and small following files. Export through the container archive API, verify that the producer emitted PAX `size`, and check metadata and traversal beyond the large entry. A sparse source file can save source disk space, but the archive transfer still may exceed 8 GiB. This test is not implemented and is not part of the default suite or CI. A large public image alone does not guarantee a large individual entry or PAX encoding.
