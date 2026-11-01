# Development

## Dependencies and layout

Composer autoloads `JakubBoucek\Tar\` from `src/` and `JakubBoucek\Tar\Tests\` from `tests/`. Runtime requirements are PHP `^8.4` and `psr/http-message` `^2.0`. Gzip and bzip2 handlers use optional `ext-zlib` and `ext-bz2` extensions.

Development dependencies are Nette Tester `~2.6.3`, PHPStan pinned to `2.3.4` and DressCode pinned to `1.3.1`. `composer.lock` is ignored. The tests require no additional mocking library.

## Verification

Run `./check.sh test`, `./check.sh phpstan` or `./check.sh dresscode` for a quick check using the existing local `vendor/` directory. The script runs `composer run` in `jakubboucek/lamp-devstack-php:latest` and forwards Docker's exit status. It does not install dependencies or run a version matrix. Docker must be running and the installed dependencies must match the image's PHP version.

For compatibility checks, use `jakubboucek/lamp-devstack-php` images with tags `8.4-cli`, `8.5-cli` and `8.6-rc-cli` (use `8.6-cli` once available). Resolve dependencies independently for each version. Checks may run in the main workspace; reinstall dependencies when changing PHP versions or dependency bounds. Enable both compression extensions so no handler tests are skipped.

For a selected PHP image:

```sh
image="jakubboucek/lamp-devstack-php:8.4-cli"
docker run --rm -v "$PWD:/app" -w /app "$image" composer install --no-interaction --prefer-dist
docker run --rm -v "$PWD:/app" -w /app "$image" composer run test
docker run --rm -v "$PWD:/app" -w /app "$image" composer run phpstan
```

`composer run test` executes `tester tests -s`. Each `*Test.php` file loads `tests/bootstrap.php`, which initializes Composer autoloading and `Tester\Environment::setup()`. Test classes extend `Tester\TestCase`, declare `@testCase` for method isolation, and end with an explicit `run()` call. Unexpected warnings fail tests; expected warnings use `Assert::error()` around `Assert::exception()` when the operation also throws. Tester 2.6 loads the system PHP configuration by default.

`composer run phpstan` analyzes `src/` at level 7. `phpstan.neon` targets PHP 8.4 even when analysis runs on newer PHP. If worker sockets are restricted, append `-- --debug` to disable parallel workers.

## Code style

`dresscode.neon` applies PER Coding Style 3.1 (`perCs`) to `src/`, `tests/` and `example.php`, with project overrides: constants use `PascalCase`, and signatures containing promoted properties always span multiple lines. Methods use `camelCase`; test methods must still start with `test` followed by an uppercase letter.

Run `composer dresscode` to check or `composer dresscode:fix` to format. The Docker equivalents are `./check.sh dresscode` and `./check.sh dresscode:fix`. For individual files and agent-friendly feedback, run `vendor/bin/dresscode fix path/to/file.php --format agent` in the same environment. Re-read rewritten files before further edits.

All six additional sets are enabled: `modernizations`, `deprecations`, `cleanup`, `correctness`, `types` and `compilerOptimizations`. These can change declarations and behavior, not just whitespace; inspect changes to public API and exception handling before accepting them. Risky fixes and a baseline are not enabled. The target PHP version comes from Composer and remains PHP 8.4. Line length uses the preset's soft warning; warnings alone do not fail the check. Review formatting changes and run tests and PHPStan after broad formatting.

PHPStan type inference is enabled through `typeAnalysis: phpstan`. CI runs DressCode with `--no-cache` so changes in other classes or PHPStan configuration are considered. `no effect: php` in the resolved configuration skips syntax newer than the PHP 8.4 target.

`nameResolution: certain` asserts that bare function and constant names can be resolved with the configured namespace declarations. The library currently declares no namespace-level functions or constants. If any are added, update the `namespaces` configuration before trusting related rewrites. `use function` imports from compiler optimizations bypass namespace fallback and must not be treated as interchangeable with consumer-provided function shims.

After changing presets, run `vendor/bin/dresscode config` to verify the effective decisions and `vendor/bin/dresscode check --no-cache` to audit all files. A clean check is not proof that every available decision ran. Use `explain <decision>` for the installed version; online documentation may describe newer CLI features.

Review changes to preset rules when updating DressCode. Agent hooks are optional and do not replace the full check; shell edits are not covered by the upstream hook.

## Tests and fixtures

- `PaxHeaderTest` covers record framing, numeric validation and explicit support boundaries. `PaxStreamReaderTest` reads GNU tar fixtures across plain, gzip and bzip2 streams and checks padding, overrides, truncation and content lifetime. [Fixture generation instructions](../tests/assets/pax/README.md) describe the independent Python verification; the default test suite only reads committed files.

- `StreamReaderTest` checks archive metadata and content hashes against committed `.tgz` archives and JSON manifests for entry sizes 0 through 1024 bytes, including block boundaries. It also checks complete traversal, skipped-content lifetime, short headers, invalid magic and truncated content or padding, with both content reads and metadata-only traversal across plain, gzip and bzip2 archives.
- `FileReaderTest` checks that the handler closes its resource exactly once after exhaustion, early termination and parser or consumer exceptions.
- `FileTest` uses real readonly `Header` values to check metadata access, all recognized entry classifications, preservation of type bytes and content object identity without triggering lazy loading.
- Header tests, `UsageTest` and `LazyContentTest` check header fields, single-use behavior and content operations. Expected warnings must actually occur; stream and temporary-file cleanup uses `finally` where applicable.
- Handler tests check suffix matching, opening resources and both native warnings and library exceptions on failure. Compression handler tests skip when their extension is absent; archive traversal tests require gzip directly.
- `tests/assets/build.php` intentionally regenerates the random archive and its matching manifest. It uses shell `tar` and `rm` and assumes the working directory is `tests/assets`. It is not a test prerequisite; run it in an isolated copy for deliberate fixture changes only.

Tester writes failure artifacts under `tests/**/output/`; these are ignored. Keep binary fixtures and JSON manifests synchronized. `example.php` stringifies each entry and is not a bounded-memory export example.

## Continuous integration and distribution

`.github/workflows/code_analysis.yaml` runs mandatory test and PHPStan jobs with both highest and lowest dependencies on PHP 8.4, 8.5 and 8.6, on pull requests and pushes to `master`. PHP 8.6 uses the prerelease build supplied by `setup-php` until stable builds are available. A separate code-style job runs `composer dresscode -- --no-cache` once on PHP 8.4 with normal dependency resolution. DressCode emits GitHub annotations automatically; CI checks without rewriting files. CI enables gzip and bzip2, disables coverage, validates Composer configuration and installs dependencies with `composer update --no-interaction --prefer-dist --no-progress` (plus `--prefer-lowest --prefer-stable` for lowest dependencies). Both Composer downloads and lock files are cached separately by PHP version and dependency mode. New commits cancel superseded runs for the same ref.

`.gitattributes` excludes tests, CI, contributor documentation, development scripts, PHPStan configuration and Git configuration files from distribution archives. The package retains `src/`, `composer.json`, `README.md` and `LICENSE`. Maintain the exclusions when adding, moving or removing development files, and inspect archive contents after changing the rules. The DressCode configuration is also excluded from distribution archives.
