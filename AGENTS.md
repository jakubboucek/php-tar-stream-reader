# Project guide

## Working conventions

- Write all repository content in English, including code, comments, documentation, commit messages and PR descriptions.
- Keep documentation concise and factual, describing current behavior. Put work history and validation milestones in commits and PR descriptions, not permanent documentation.
- Store project and code knowledge here or in linked `docs/` files. Personal memory is reserved for user preferences and feedback.
- Preserve PHP 8.0 compatibility (`composer.json` requires `~8.0`). Use strict types, four-space indentation and the existing brace style. Avoid unrelated formatting changes.
- Prefer native types where PHP 8.0 supports them; retain PHPDoc for resources and collection types.

## Project map

This is `jakubboucek/tar-stream-reader`, a standalone, read-only PHP library for sequential TAR archive access, with optional gzip and bzip2 handlers. There is no application framework, database or frontend build.

- [README.md](README.md): installation and introductory usage.
- [docs/architecture.md](docs/architecture.md): readers, content lifecycle and implemented format boundaries.
- [docs/development.md](docs/development.md): dependencies, checks, fixtures and CI.
- `src/FileReader.php` and `src/StreamReader.php`: filename and resource entry points.
- `src/File.php`: entry metadata and content access.
- `src/Parser/`: header parsing, lazy content and single-use tracking.
- `src/FileHandler/`: plain, gzip and bzip2 resource handling.
- `tests/`: PHPUnit tests and committed binary fixtures.

## Design constraints

- Preserve the PSR-4 namespace `JakubBoucek\Tar\` and compatibility with both PSR HTTP Message 1.x and 2.x.
- Keep content lazy and direct exports bounded in memory. Do not introduce whole-archive buffering or require backward seeking on the archive stream.
- Consume or materialize entry content before advancing the iterator. Preserve closure of skipped content and the distinction between loaded content and direct export.
- Compression extensions are optional at runtime. Keep explicit handler selection and extension availability checks.
- PAX metadata is not supported. Do not imply complete TAR compliance or arbitrary non-seekable stream support; consult the architecture guide before changing parsing.
- Cover behavior changes with focused PHPUnit regression tests, especially archive padding, content lifetime and error paths.
- Run PHP checks in `jakubboucek/lamp-devstack-php` Docker images, as in the related projects. Resolve dependencies separately for each PHP version; use disposable copies to preserve an existing installation. See the development guide.
- Do not commit `vendor/`, `composer.lock`, PHPUnit caches or generated temporary files. Preserve committed archive fixtures unless deliberately updating their matching expectations.
- Preserve Composer download and lock-file caches when editing CI. Keep workflow changes in separate commits and verify CI before proceeding to another workflow change when publishing is authorized.
