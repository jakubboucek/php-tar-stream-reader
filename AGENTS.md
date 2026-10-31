# Project guide

## Working conventions

- Write all repository content in English, including code, comments, documentation, commit messages and PR descriptions.
- Keep documentation concise and factual, describing current behavior. Put work history and validation milestones in commits and PR descriptions, not permanent documentation.
- Store project and code knowledge here or in linked `docs/` files. Personal memory is reserved for user preferences and feedback.
- Preserve PHP 8.4 compatibility (`composer.json` requires `^8.4`); CI tests PHP 8.4–8.6. Use strict types and the DressCode `perCs` preset (PER Coding Style 3.1), with PascalCase constants as configured in `dresscode.neon`. Avoid unrelated formatting changes.
- Prefer native types where PHP 8.4 supports them; retain PHPDoc for resources and collection types.

## Project map

This is `jakubboucek/tar-stream-reader`, a standalone, read-only PHP library for sequential TAR archive access, with optional gzip and bzip2 handlers. There is no application framework, database or frontend build.

- [README.md](README.md): installation and introductory usage.
- [docs/architecture.md](docs/architecture.md): readers, content lifecycle and implemented format boundaries.
- [docs/development.md](docs/development.md): dependencies, checks, fixtures and CI.
- `src/FileReader.php` and `src/StreamReader.php`: filename and resource entry points.
- `src/File.php`: entry metadata and content access.
- `src/Parser/`: header parsing, lazy content and single-use tracking.
- `src/FileHandler/`: plain, gzip and bzip2 resource handling.
- `tests/`: Nette Tester tests and committed binary fixtures.

## Design constraints

- Preserve the PSR-4 namespace `JakubBoucek\Tar\` and compatibility with PSR HTTP Message 2.x. Version 1.x is unsupported because its untyped interface parameters are incompatible with the typed stream implementation.
- Keep content lazy and direct exports bounded in memory. Do not introduce whole-archive buffering or require backward seeking on the archive stream.
- Consume or materialize entry content before advancing the iterator. Preserve closure of skipped content and the distinction between loaded content and direct export.
- Compression extensions are optional at runtime. Keep explicit handler selection and extension availability checks.
- PAX metadata is not supported. Do not imply complete TAR compliance or arbitrary non-seekable stream support; consult the architecture guide before changing parsing.
- Tests use `Tester\TestCase`, a file-level `@testCase` annotation, `tests/bootstrap.php` and an explicit `run()` call. Assert expected warnings and exceptions; do not suppress unexpected errors. Keep temporary files unique and clean up resources in `finally`.
- Cover behavior changes with focused Nette Tester regression tests, especially archive padding, content lifetime and error paths.
- Run PHP checks in `jakubboucek/lamp-devstack-php` Docker images, as in the related projects. Resolve dependencies separately for each PHP version; checks can run in the main workspace, reinstalling dependencies as needed. See the development guide.
- Run `./check.sh style` alongside tests and PHPStan. Use `./check.sh style:fix` to format; for targeted agent edits use the project-local DressCode with `--format agent` and re-read rewritten files. Preserve test discovery when renaming methods. Do not introduce baselines, suppressions or risky fixes without an explicit reason and approval.
- Do not commit `vendor/`, `composer.lock`, Tester output files or generated temporary files. Preserve committed archive fixtures unless deliberately updating their matching expectations.
- Maintain `.gitattributes` whenever adding, moving or removing development-only files or directories. Exclude tests, CI, tooling and contributor documentation with `export-ignore`; keep runtime source, `composer.json`, `README.md` and `LICENSE` in distribution archives. Verify the archive contents after changing these rules.
- Keep README useful for installed-package consumers: include a concise API map, essential usage limits and GitHub links to the full documentation. Do not link relatively to excluded files. Keep the linked documentation branch current when publishing, and direct readers to the installed version's tag or Composer source reference.
- Preserve Composer download and lock-file caches when editing CI. Keep workflow changes in separate commits and verify CI before proceeding to another workflow change when publishing is authorized.
