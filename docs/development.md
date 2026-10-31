# Development

## Dependencies and layout

Composer autoloads `JakubBoucek\Tar\` from `src/` and `JakubBoucek\Tar\Tests\` from `tests/`. Runtime requirements are PHP `~8.4` and `psr/http-message` `~1.0 || ~2.0`. Gzip and bzip2 support use optional `ext-zlib` and `ext-bz2` extensions.

Development dependencies are PHPUnit `^9.0 || ^10.0` and PHPStan pinned to `2.3.4`. `composer.lock` is ignored, so dependency resolutions can vary. There is no application bootstrap beyond Composer autoloading.

## Verification

For a quick check using the existing local `vendor/` directory, run `./check.sh test` or `./check.sh phpstan`. The script requires exactly one of these arguments, runs `composer run` in `jakubboucek/lamp-devstack-php:latest`, and returns Docker's exit status. It does not install dependencies or run a PHP version matrix. Docker must be running and the installed dependencies must be compatible with the image's PHP version.

Use `jakubboucek/lamp-devstack-php` Docker images for PHP execution. The committed CI matrix covers PHP 8.4 through 8.6. Corresponding image tags are `8.4-cli`, `8.5-cli` and `8.6-rc-cli` (use `8.6-cli` once available).

For each version, use a fresh disposable project copy without `vendor/` or `composer.lock`, or remove those generated paths in a dedicated test checkout before installing. Do not delete an existing working installation just to run documentation checks. Run versions sequentially if they share a writable directory.

From a disposable checkout:

```sh
image="jakubboucek/lamp-devstack-php:8.4-cli"
docker run --rm -v "$PWD:/app" -w /app "$image" composer install --no-interaction --prefer-dist
docker run --rm -v "$PWD:/app" -w /app "$image" composer run test
docker run --rm -v "$PWD:/app" -w /app "$image" composer run phpstan
```

`composer run test` executes `phpunit tests`. `phpunit.xml` loads `vendor/autoload.php`, enables failure on warnings and targets a PHPUnit 10.2 XML schema.

`composer run phpstan` analyzes `src/` at level 7, with `phpstan.neon` targeting PHP 8.4 even when analysis runs on newer PHP. If worker sockets are restricted, append `-- --debug` to run without parallel workers.

## Tests and fixtures

- `StreamReaderTest` checks entry metadata and content hashes against committed `.tgz` archives and JSON manifests. The fixtures cover entry sizes from 0 through 1024 bytes, including block boundaries.
- `FileTest`, header tests, `UsageTest` and `LazyContentTest` cover metadata delegation, header fields, single-use behavior and content stream operations.
- Handler tests check suffix matching, resource opening and failure behavior. Compression handler tests can skip when the extension is absent; stream traversal tests use gzip directly.
- `tests/assets/build.php` regenerates the random archive and its manifest, uses shell `tar` and `rm`, and assumes the working directory is `tests/assets`. It is not a normal test prerequisite. Run it only for intentional fixture changes, preferably in an isolated copy, and keep the archive and manifest synchronized.

`example.php` is a CLI archive inspection example; it stringifies each entry's content and is not a bounded-memory export example.

## Continuous integration and distribution

`.github/workflows/code_analysis.yaml` runs separate PHPUnit and PHPStan jobs for each PHP version, on pull requests and pushes to `master`. It installs dependencies with `composer update --no-progress`, caches Composer downloads and lock files, and disables coverage.

`.gitattributes` excludes tests, CI files, the example and Git metadata configuration files from distribution archives. There is no formatter or additional static-analysis configuration committed to the project.
