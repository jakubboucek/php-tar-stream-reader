# Compatibility with v2.0.2

The planned release is **2.1.0**, as selected by the maintainer after assessing the limited usage of this library and the low likelihood of consumers extending its classes. This release intentionally includes the compatibility changes below; the minor version number does not imply full backward compatibility with v2.0.2.

## Breaking changes

| Area | v2.0.2 | Current branch | Consumer impact |
| --- | --- | --- | --- |
| PHP requirement | `~8.0` (PHP 8.x from 8.0) | `^8.4` | PHP 8.0–8.3 are no longer supported. |
| PSR HTTP Message | `~1.0 || ~2.0` | `^2.0` | Consumers restricted to 1.x cannot resolve the new version. The old 1.x allowance was already incompatible with typed stream method parameters. |
| `File` inheritance | Ordinary class | Readonly class | Existing non-readonly subclasses fail to load. Dynamic properties are forbidden; subclass state must obey readonly restrictions. |
| `LazyContent::getMetadata()` | No native return type | `mixed` return type | Existing overrides without a return type fail to load. They need `mixed` or a compatible narrower type. |

An explicit `mixed` return type is compatible with the untyped PSR interface, but it is not compatible with every existing child override of the concrete class. Calls to the method are unchanged by the type declaration.

Both classes remain extendable; neither is marked internal or final. Subclass compatibility therefore matters even though ordinary reader usage does not need subclasses.

## Other observable changes

- `FileReader` closes its handler on normal completion, parser exceptions and destruction of an unfinished generator. A retained generator still keeps the resource open.
- `toFile()` rejects already closed content before truncating a destination and closes the output stream when export throws. It is not an atomic file replacement operation.
- Skipped payloads and padding are read in bounded chunks. Truncated archives can now throw during metadata-only traversal; listing an archive now reads skipped bytes rather than seeking past them.
- `getMetadata()` returns all metadata only for a null key; empty-string and `"0"` keys now return `null` when absent, rather than the whole array.
- `LazyContent::read()` catches `Throwable`, rather than only `Exception`. A PHP `Error` or `TypeError` in its materialization closure is now wrapped in the library `RuntimeException`; the original is available through `getPrevious()`.
- Compiler-optimized built-in functions are explicitly imported in several namespaces. Consumer shims for those namespace-qualified functions no longer intercept the calls.

These should be described in release notes, even where the change fixes a defect.

## Preserved contracts

The runtime class and interface names, public/protected method names, parameter names, defaults and parameter types are preserved. Apart from `getMetadata(): mixed`, public/protected return types are unchanged. Constructor property promotion preserves positional and named calls. Explicit `Stringable` declarations do not add a new requirement for callers: PHP already recognizes classes implementing `__toString()` as stringable.

Private readonly properties and promotion are implementation changes for ordinary callers. The readonly modifier on the public `File` class is different because it constrains inheritance.

Nette Tester, PHPStan and DressCode changes affect contributors, not runtime dependency installation. Composer development scripts now use `dresscode` and `dresscode:fix`.

## Release preparation

- Keep the distribution limited to runtime source, Composer metadata, README and license.
- README documentation links target `master`, the maintained release branch. Keep them aligned with the publishing branch.
- Run the remote CI matrix before tagging. Local checks do not establish that a GitHub workflow run passed.
- Do not add a Composer `version` field; publish the selected version through a Git tag.

References: [Semantic Versioning](https://semver.org/), [PHP readonly classes](https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly).
