# SHO-5 per-column modifiers

Date: 2026-11-11. Branch: `feature/sho-5-per-column-modifiers`, based on the local `feature/sho-2-sho-3` checkout. Changes are local and uncommitted.

The later [independent test](2026-11-11-sho-5-independent-test.md) found three output defects and two validation usability issues. See the [local fixes and verification limits](2026-11-11-sho-5-independent-fixes.md); the results below retain their original pre-fix scope.

## Behavior

The active Columns Map UI Component includes a native DynamicRows editor. Rows select an exact column name, operation, and argument. Drag order is normalized through the existing `FeedFormData` JSON boundary; literal strings, empty lists, and row deletion follow that editor's existing persistence contract.

`ModifierChain` validates an allowlist of thirteen operations and their arguments. A save-before observer covers Admin, imported and programmatic feed saves. The filter separately validates raw stored configuration at generation time. Reading the raw value matters: `Feed::getConfig()` otherwise treats `false` as an absent setting. The new malformed-false regression failed before that correction and passes afterward.

`AdapterAbstract::afterMap()` applies the chain after built-in directive formatting and before empty-column checks. Each repeated cell is processed independently. Cleaning defers the limit for modified columns, preserves markup for explicit Strip tags / Escape HTML operations, and retains previous behavior for unmodified columns. Final separator normalization and column limits run after the chain. The effective adapter currency supplies price formatting when no currency suffix or explicit modifier currency exists.

Price directives still perform their built-in two-decimal formatting before the modifier chain. Use raw attributes or Conditional Value numeric outputs for greater source precision. Date input is absolute ISO date/time; invalid dates and nonnumeric values remain unchanged. Price formatting does not perform currency conversion. Secure URL changes only the leading HTTP scheme or protocol-relative prefix; it does not verify the endpoint.

## Verification

The evidence directory is `/private/tmp/sho5-20261111/`.

- PHPUnit 12.5 on the installed Mage-OS framework and PHPUnit 9.6 on its matching older framework: **1077 tests pass on both runners**, with 2945 assertions on PHPUnit 12 and 2558 on PHPUnit 9. Results: `unit12.txt` and `unit9.txt`.
- Focused modifier coverage executes the real chain, cleaner, final limits, and adapter `afterMap()` boundary. Clover output: `coverage.xml`. The final coverage run executed 71 of 72 chain statements and 117 of 123 filter statements; the adapter test calls the real finalization method, with preceding formatting stubbed to its expected output.
- Unit coverage includes ordered/repeated values, Unicode and ISO-8859-1 text, formatting, invalid input, URL prefixes, unchanged legacy cleaning, final limits, effective currency, raw stored validation, provider transport/order/reprojection, null clearing, and metadata/options.
- Browser fixture: six scripted checks passed using the actual metadata, native Magento DynamicRows/remote templates, and the module select component. Saved column/operation/value rendering, trailing spaces, literal provider values, adding rows, and setting values passed. A separate DOM edit/delete removed the new row from provider data. Fixture files and metadata: `index.html`, `modifiers.js`, `metadata.json`. This standalone fixture supplies the UI layout defaults normally supplied by Magento; it is not an installed Admin form.
- The real generic CSV writer round-trips appended quotes/commas/Unicode and HTML-escaped values through a temporary stream.
- Scoped Magento coding standard and PHPStan level 8 checks; no PHPStan baseline additions.
- 79 frontend/CI JavaScript checks and 8 installed-framework JavaScript checks, Composer validation, Magento XML validation, PHP syntax, wiki validation, and consolidation validation on a clean source snapshot.

Reproduction commands from the module root:

```sh
MAGENTO_ROOT=/Users/matt/code/mageos-latest /opt/homebrew/opt/php@8.4/bin/php /Users/matt/code/mageos-latest/vendor/bin/phpunit -c phpunit.xml.dist
MAGENTO_ROOT=/Users/matt/code/mageosdemo/src /opt/homebrew/opt/php@8.3/bin/php /Users/matt/code/mageosdemo/src/vendor/bin/phpunit -c phpunit.xml.dist
node --test dev/tests/frontend/*.test.cjs dev/tests/ci/*.test.cjs
MAGENTO_ROOT=/Users/matt/code/mageos-latest node --test dev/tests/magento-ui-form.test.cjs
php dev/tests/validate-magento-xml.php /Users/matt/code/mageos-latest
php dev/tests/validate-wiki.php
```

PHP 8.1 syntax checks passed for all 564 PHP/PHTML source-snapshot files. Scoped standards results and the PHPStan configuration are retained in `phpcs.txt` and `phpstan.txt` in the evidence directory. No complete CI or installed integration-suite result is claimed.

## Acceptance limits

Provider projection and JSON round-trip tests are not an installed Admin POST/database save/reopen. This work does not establish full catalog generation, composite inheritance, custom-option generation, preview, schedules, queues, external recipient acceptance, or production behavior for SHO-5. Follow the new SHO-5 section of `ACCEPTANCE-TEST-PLAN.md` on a disposable installed store before release.

The checkout's existing `CONTRIBUTING.md` edits and unrelated review files were preserved. Direct consolidation validation sees an unrelated nested `.claude/worktrees` tree, so its source-only result is recorded separately. No commit, push, deployment, or wiki publication was performed.
