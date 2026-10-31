# Development

## Dependencies and layout

Composer autoloads `JakubBoucek\Tar\` from `src/` and `JakubBoucek\Tar\Tests\` from `tests/`. Runtime requirements are PHP `^8.4` and `psr/http-message` `^2.0`. Gzip and bzip2 handlers use optional `ext-zlib` and `ext-bz2` extensions.

Development dependencies are Nette Tester `~2.6.3` and PHPStan pinned to `2.3.4`. `composer.lock` is ignored. The tests require no additional mocking library.

## Verification

Run `./check.sh test` or `./check.sh phpstan` for a quick check using the existing local `vendor/` directory. The script runs `composer run` in `jakubboucek/lamp-devstack-php:latest` and forwards Docker's exit status. It does not install dependencies or run a version matrix. Docker must be running and the installed dependencies must match the image's PHP version.

For compatibility checks, use `jakubboucek/lamp-devstack-php` images with tags `8.4-cli`, `8.5-cli` and `8.6-rc-cli` (use `8.6-cli` once available). Resolve dependencies independently for each version in a fresh disposable copy without `vendor/` or `composer.lock`. Keep an existing working installation intact. Enable both compression extensions so no handler tests are skipped.

From a disposable checkout:

```sh
image="jakubboucek/lamp-devstack-php:8.4-cli"
docker run --rm -v "$PWD:/app" -w /app "$image" composer install --no-interaction --prefer-dist
docker run --rm -v "$PWD:/app" -w /app "$image" composer run test
docker run --rm -v "$PWD:/app" -w /app "$image" composer run phpstan
```

`composer run test` executes `tester tests -s`. Each `*Test.php` file loads `tests/bootstrap.php`, which initializes Composer autoloading and `Tester\Environment::setup()`. Test classes extend `Tester\TestCase`, declare `@testCase` for method isolation, and end with an explicit `run()` call. Unexpected warnings fail tests; expected warnings use `Assert::error()` around `Assert::exception()` when the operation also throws. Tester 2.6 loads the system PHP configuration by default.

`composer run phpstan` analyzes `src/` at level 7. `phpstan.neon` targets PHP 8.4 even when analysis runs on newer PHP. If worker sockets are restricted, append `-- --debug` to disable parallel workers.

## Tests and fixtures

- `StreamReaderTest` checks archive metadata and content hashes against committed `.tgz` archives and JSON manifests for entry sizes 0 through 1024 bytes, including block boundaries. It also checks complete traversal, skipped-content lifetime, short headers, invalid magic and truncated content or padding, with both content reads and metadata-only traversal across plain, gzip and bzip2 archives.
- `FileReaderTest` checks that the handler closes its resource exactly once after exhaustion, early termination and parser or consumer exceptions.
- `FileTest` checks metadata delegation using `tests/Doubles/HeaderStub.php` and verifies content object identity without triggering lazy loading. The stub keeps file/directory flags independent of the raw type to detect incorrect delegation.
- Header tests, `UsageTest` and `LazyContentTest` check header fields, single-use behavior and content operations. Expected warnings must actually occur; stream and temporary-file cleanup uses `finally` where applicable.
- Handler tests check suffix matching, opening resources and both native warnings and library exceptions on failure. Compression handler tests skip when their extension is absent; archive traversal tests require gzip directly.
- `tests/assets/build.php` intentionally regenerates the random archive and its matching manifest. It uses shell `tar` and `rm` and assumes the working directory is `tests/assets`. It is not a test prerequisite; run it in an isolated copy for deliberate fixture changes only.

Tester writes failure artifacts under `tests/**/output/`; these are ignored. Keep binary fixtures and JSON manifests synchronized. `example.php` stringifies each entry and is not a bounded-memory export example.

## Continuous integration and distribution

`.github/workflows/code_analysis.yaml` runs mandatory test and PHPStan jobs with both highest and lowest dependencies on PHP 8.4, 8.5 and 8.6, on pull requests and pushes to `master`. PHP 8.6 uses the prerelease build supplied by `setup-php` until stable builds are available. CI enables gzip and bzip2, disables coverage, validates Composer configuration and installs dependencies with `composer update --no-interaction --prefer-dist --no-progress` (plus `--prefer-lowest --prefer-stable` for lowest dependencies). Both Composer downloads and lock files are cached separately by PHP version and dependency mode. New commits cancel superseded runs for the same ref.

`.gitattributes` excludes tests, CI, contributor documentation, development scripts, PHPStan configuration and Git configuration files from distribution archives. The package retains `src/`, `composer.json`, `README.md` and `LICENSE`. Maintain the exclusions when adding, moving or removing development files, and inspect archive contents after changing the rules. There is no committed formatter configuration.
