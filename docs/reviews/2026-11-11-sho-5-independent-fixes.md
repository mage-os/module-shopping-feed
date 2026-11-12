# SHO-5 independent-test fixes

Date: 2026-11-11. Branch: `feature/sho-5-per-column-modifiers`. Local, uncommitted follow-up to the [independent end-to-end report](2026-11-11-sho-5-independent-test.md).

## Changes

1. **Escaped entities and delimiters.** Save and generation reject Escape HTML when an unenclosed delimiter consists only of characters that can occur in HTML entities: letters, digits, `&`, `#`, and `;`. This deliberately conservative rule covers named and numeric references, including multi-character delimiters. Set **Cell Enclosure** to a double quote, or choose a delimiter containing a character outside that alphabet. Generic CSV retains its automatic enclosure. Enclosed semicolon, ampersand, and hash output preserves literal and generated entities through the real writer. The fix does not silently change a feed's output format.
2. **Strip tags.** Remove script/style blocks and their contents before stripping ordinary tags. Other text and existing entities retain their previous semantics, including no decoding of entity-encoded tags. The guide now documents that an unfinished tag can discard trailing text.
3. **Round.** Use decimal formatting with the requested precision, then remove fractional trailing zeros. Output has no exponent or negative zero and is independent of PHP's float-to-string `precision` setting. Underlying PHP floating-point limits remain; this is not arbitrary-precision arithmetic.
4. **Unknown columns.** Reject unknown modifier column names during save with the offending name. Compare against the submitted Columns Map, including columns added in that same submission, rather than a cached earlier map. Exact names are preserved; no silent trimming or case conversion occurs. A removed column requires removing or correcting its modifier rows in the same save.
5. **Validation messages.** Malformed lists receive “Column modifiers must be a list of rows.” More than 200 rows receives “Column modifiers cannot exceed 200 rows.” Null and empty-string clearing remain compatible.

The save observer and runtime filter share output-compatibility validation. The default cleaner and output of feeds without modifiers were not intentionally changed. The pre-existing custom-option column alignment, legacy non-UTF-8 handling, preview deduplication, and cross-feed currency findings remain outside this fix.

## Verification

Evidence: `/private/tmp/sho5-fixes-20261111/`.

- Restored the eight regression cases from the supplied report into `ModifierRegressionTest.php`. All eight failed against the implementation before these fixes (`regression-red.txt`). They pass after the fixes on PHPUnit 9/PHP 8.3 and PHPUnit 12/PHP 8.4.
- Added save/runtime delimiter rejection, enclosed entity preservation, unknown/padded column rejection, submitted-map/cache behavior, separate error messages, script/style edge cases, real delimited-writer round trips, and unchanged non-Escape separator behavior. The two new test classes pass 31 cases with 53 assertions. Before implementation, 12 of the initial 14 validation tests failed for the intended missing behavior (`validation-red.txt`).
- Full PHPUnit 12.5 suite on PHP 8.4.27: **1108 tests, 2998 assertions** (`unit12.txt`).
- Full PHPUnit 9.6 suite on PHP 8.3: **1108 tests, 2611 assertions** (`unit9.txt`).
- The five Round output cases pass under PHP 8.1.34, 8.2.34, 8.3.35, 8.4.27, and 8.5.11, each with `precision` set to 3, 14, 17, and -1: **100 checks**. Reproduction script: `round-versions.php`.
- Coverage executes the real modifier engine and filter. `ModifierChain` covers 95 of 96 statements; `compileForFeed`, `stripTags`, and `formatNumber` all execute (`coverage.xml`). The existing invalid-encoding exception remains uncovered.
- Scoped Magento coding standards and PHPStan level 8 pass for the changed implementation and regression files. No baseline entries were added. The full PHPStan attempt stopped in the dependency autoloader on `Magento\Framework\EntityManager\OperationInterface` while processing unchanged code; no complete static-analysis result is claimed (`phpstan.txt`, `phpstan-scoped.txt`).

Run the suites from the module root:

```sh
MAGENTO_ROOT=/Users/matt/code/mageos-latest /opt/homebrew/opt/php@8.4/bin/php /Users/matt/code/mageos-latest/vendor/bin/phpunit -c phpunit.xml.dist
MAGENTO_ROOT=/Users/matt/code/mageosdemo/src /opt/homebrew/opt/php@8.3/bin/php /Users/matt/code/mageosdemo/src/vendor/bin/phpunit -c phpunit.xml.dist
```

## Remaining acceptance

The earlier independent report remains evidence for its exact pre-fix snapshot. This follow-up does not claim another installed Admin save/reopen, 3035-product generation, queue/batch comparison, or preset-recipient acceptance run. The report's `/private/tmp/sho5i-20261111` environment was no longer present when this work began. Its regression source was recovered from the pasted appendix; no old test resources were changed or deleted.

On an isolated installed store, repeat the three output regressions, invalid/valid delimiter saves, unknown-column save rejection and error recovery, same-save column addition, and the original generation differential checks. Nothing was committed, pushed, deployed, or published.
