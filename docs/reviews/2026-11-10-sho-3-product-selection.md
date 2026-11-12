# SHO-3 product selection by attribute condition

Date: November 10, 2026 (America/Indiana/Indianapolis).

Implemented locally and verified on Mage-OS 4.0.0 with PHP 8.4. The changes are uncommitted. Nothing was pushed, published, or deployed to production.

## Behavior

**Product Filters > Product selection conditions** supports up to 50 All/Any conditions. Each condition reads the output product or its parent in the feed store. The editor includes ordinary product attributes without requiring the promo-rule flag, visibility, status, base-image presence, and case-sensitive SKU wildcards (`*` and `?`). Structured tier-price, stock-status, and gallery data are not offered as scalar condition attributes.

SHO-2 Conditional Value and SHO-3 selection use the same validator, Magento comparison adapter, compiled matcher, and condition editor. Output branches remain specific to Conditional Value. Selection uses the existing feed configuration persistence and import/export format; no schema change is needed.

Selection narrows existing eligibility. It runs before duplicate tracking and evaluates parent and child output rows independently. A parent that fails selection still allows matching children. Existing category, product skip, visibility, status, type, stock, and column filters remain effective.

Associated rows use the active parent. Standalone rows can match any one direct parent assigned to the feed website, including disabled parents. All parent conditions must match the same parent. Missing parents never satisfy parent conditions, including negative comparisons. Parent attribute collections explicitly use the feed store. No product result is cached between evaluations.

Empty rules disable the filter. Malformed rules fail model save and fail generation before the product loop, including when the catalog selection is empty. Predicate caches are bounded. Rules evaluate loaded products rather than adding SQL collection filters.

## Verification

| Check | Result |
| --- | --- |
| Full PHP unit suite | 991 tests, 2771 assertions passed |
| Focused selection and Conditional Value suite with Xdebug | 40 tests, 157 assertions passed; Clover records execution of the real matcher, validator, comparison code, simple selection hook, and composite parent/child mapping paths |
| Existing frontend and CI-policy JavaScript suites | 79 tests passed |
| Actual-framework JavaScript suites | 13 tests passed, including five new shared-editor tests using Magento's Knockout |
| SHO-2 remote-template browser regression | 8 checks passed against the actual Magento template engine |
| Scoped Magento coding standard | No code errors or warnings; the installed tool emits its existing deprecation notices |
| Scoped PHPStan level 8 | No errors; existing baseline used without additions |
| Clean-source validation | Consolidation, wiki validation, and all 26 Magento XML schema checks passed |
| Dependency injection | `setup:di:compile` passed after the new dependency wiring was installed |
| Installed generation and persistence | 11 checks passed |
| Installed preview, batch and store boundaries | 8 checks passed |
| Admin save/reopen and resulting output | Passed; the browser-saved disabled-parent rule generated only `sho3-other` |

The generation cases cover numeric selection, All/Any behavior, configurable parent failure with a matching child, disabled-parent lookup for a standalone row, image presence/absence, duplicate prevention, rejected-save preservation, attribute option availability, and simultaneous SHO-2 conditional output. The boundary cases cover SKU preview, an unpublished partial batch file, persisted batch offsets, final queue cleanup, exactly-once output in that test, malformed stored rules with an empty collection, and parent name overrides between store views.

Unit tests also cover grouped and bundle adapter paths, multiple-parent isolation, an explicit parent taking precedence over other links, missing parents, undefined attributes, dates, multiselects, unknown operators/attributes/scopes, limits, wildcard punctuation and case sensitivity, and false configuration values. Their parent resolver is mocked; the actual parent lookup was exercised in the installed configurable and standalone cases.

In the browser, a SKU wildcard and a Parent product / Enable Product / Disabled condition were entered, saved, and reopened. The scopes, operator, wildcard, and numeric status option survived the server round trip. Browser errors were empty. The reopened editor was captured and visually inspected.

## Commands and evidence

Run the PHP suite from the module checkout:

```bash
MAGENTO_ROOT=/private/tmp/shopping-feed-functional-20261109/app \
  /opt/homebrew/opt/php@8.4/bin/php \
  /private/tmp/shopping-feed-functional-20261109/app/vendor/bin/phpunit -c phpunit.xml.dist

node --test dev/tests/frontend/*.test.cjs dev/tests/ci/*.test.cjs

MAGENTO_ROOT=/private/tmp/shopping-feed-functional-20261109/app \
  node --test dev/tests/product-selection-editor.test.cjs dev/tests/magento-ui-form.test.cjs
```

Local evidence and repeatable installed-store scripts are under `/private/tmp/sho3-20261110/`:

- `unit.txt`, `focused-unit.txt`, `coverage.xml`, `javascript.txt`, `framework-javascript.txt`
- `phpcs.txt`, `phpstan.txt`, `phpstan.neon`, `autoload.php`, `compile.txt`
- `functional.php`, `functional.txt`, `functional-results.json`
- `boundaries.php`, `boundaries.txt`, `batch-output.tsv`, `batch-feed-id.txt`
- `browser-output.php`, `browser-output.txt`, `browser-output.tsv`, `editor.png`
- `created.json` lists synthetic fixture IDs. The browser acceptance feed is ID 46.
- `installed-before/` contains the replaced disposable-store files. The installed store also contains the earlier SHO-1 test work; the module checkout retains its separate existing changes.

The repository's consolidation scanner traverses the unrelated `.claude/worktrees` directory and reports legacy identifiers there. The same validator and XML checks passed against `/private/tmp/sho3-20261110/source`, a snapshot of tracked files plus nonignored local files without nested worktrees. No validator exemption was added.

## Limits

The installed-store tests used a small synthetic catalog and Generic feeds. Grouped and bundle selection passed the real adapter methods in unit tests, but their complete installed-store generation was not repeated. Other Magento versions, every feed destination, restricted Admin roles, and large-catalog performance were not tested in this task. No external recipient ingestion or production behavior is claimed.

Base-image presence means the `image` attribute is populated. It does not prove the file exists or examine gallery images. Parent rules can add relationship and EAV reads for standalone products. Selection does not reduce the underlying collection scan count.
