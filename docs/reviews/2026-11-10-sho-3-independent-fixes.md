# SHO-3 independent findings: fixes and verification

Date: November 10, 2026

All eight findings from [the independent review](2026-11-10-sho-3-independent-test.md) have been addressed locally. The queue concern was reproduced and recovery verified. Everything remains uncommitted. Existing SHO-1 runtime changes, SHO-2 work, and unrelated working-tree files were preserved.

## Changes

| Finding | Resolution | Verification |
| --- | --- | --- |
| F1: missing or misdirected validation errors | Bind and replace the feed logger before validation. Record the validation reason and Error status, including direct generation. Shared predicate errors use neutral wording. | The deleted-attribute reproduction now logs its reason. A preceding feed's log remains unchanged. |
| F2: numeric-looking edits lost | Compare decoded configuration values strictly before saving. | Real database and Admin Save controller roundtrips preserve `00123` to `123` and `100` to `1e2`. |
| F3: repeated skip counting | Count a rejected product/parent candidate once per outer collection product. Log the parent context separately. | Local Inventory falls from 59 evaluations to 30 rejected candidates, retaining the expected two output rows. Batch and full-run skip totals agree. |
| F4: byte-oriented SKU wildcard | Use UTF-8 matching so `?` consumes one Unicode character. | Accented, CJK, and emoji unit cases pass; the independent `sho3i-s-é` generation case passes. |
| F5: Date and Time treated as text | Treat both date frontend types as dates. Compare the stored calendar day with a validated `YYYY-MM-DD` value, ignoring time and without timezone conversion. | The stored `2026-03-01 15:30:00` value matches `2026-03-01`; invalid dates fail validation. Fresh metadata offers native date operators. |
| F6: malformed imports and unsupported attributes | Require a condition list. Share supported-attribute checks with the editor and validate native option values. Preserve legacy keyed rows in the editor for repair, then save a list after an edit. | Real Save rejects keyed objects without changing stored data. Structured attributes and invalid option values fail validation. All editor malformed-data cases render without errors. |
| F7: null clearing silently fails | Normalize an explicitly supplied selection `null` to an empty list before persistence. | A fresh model load sees selection disabled. Editor-style clearing also persists. |
| F8: repeated parent queries and loads | Cache relation windows of 500 child IDs and up to 256 parent models, load only predicate attributes, and reset between generations/batches and stores. | Query profile, full generation, batch parity, and store 1/store 2/store 1 reuse checks pass. |

The F3 metric is rejected **output candidates**, not distinct catalog products or predicted final rows. A standalone product and the same product under a parent are separate candidates. The generic two-selected-products case still has 37 legitimate rejected candidates across 30 scanned products; repeated checks of the same candidate do not add to that count. Rejection happens before custom-option and inventory expansion, so this is not a prediction of expanded row counts. The user guide now states this distinction.

## Queue recovery

Nine checks passed through the real `Cron\Process`, queue collection, and database:

1. Invalid configuration returns failure and marks the feed Error.
2. The queue row remains `is_read=1`.
3. The next lookup selects that same read row.
4. Retrying the unchanged invalid rule fails again.
5. Its reason appears in the current feed log.
6. No error leaks into the preceding feed's log.
7. Correcting the configuration allows the same queue row to complete.
8. Successful completion removes the row.
9. The feed leaves Error status.

No queue implementation change was necessary. Existing behavior retries invalid configurations until corrected; this test does not establish a backoff policy or retry limit. Recovery of a read batch restarts at offset zero, as the existing queue implementation specifies.

## Independent reruns

- Functional generation: **73/73 passed**.
- Save validation, persistence, preview, shared services, SHO-2 combined behavior, and destination boundaries: **76/76 passed**.
- Real Magento UI provider and Knockout template engine in a browser: **32/32 passed**, with no browser errors.
- Real Admin Save controller: exact numeric string, keyed-object rejection, browser-edited rule, integer option value, clearing, and invalid-save recovery all passed.
- Parent cache across store 1, store 2, and store 1 again: **3/3 passed**.
- Seven batches of 500: **2,002 rows**, identical to a full run, no duplicates, **1,034 skips** in both runs. Publication occurred only after the last batch; no queue row remained.
- Generic XML/delimited, Google Shopping, and Local Inventory outputs retained the expected eligible fixture IDs. Meta and Pinterest had no selected fixture rows; their controls also contained no SHO3I fixture rows. These two destinations therefore establish exclusion behavior, not a positive fixture acceptance case.

The independent scripts were copied to `/private/tmp/sho3-fixes-20261110/independent/`; originals were retained. Harness adjustments were necessary and explicit:

- Exclude the separate `sho3iperf-*` performance products from functional collections and use the corresponding collection count for progress assertions. Performance and batch tests still scan the full workload.
- Correct the original wildcard expectation: `?` legitimately matches the `[` character.
- Test null clearing as disabled selection, allowing its documented canonical empty-list representation.
- Query the actual queue field, `is_read`, rather than the harness's nonexistent `is_running` field.
- Inspect XML item IDs instead of treating image filenames as exported SKUs.
- Compare empty fixture outputs with their destination controls.
- Use the independent browser harness's saved deleted-attribute rule with fresh server metadata. Its original live feed had already been repaired, so it could not serve as a deleted-attribute fixture anymore.
- Verify skip counts by product/parent context rather than requiring each product ID to appear once across different contexts.

Empty wildcard patterns remain valid literal patterns. They are not a validation error.

## Parent lookup measurement

The independent performance workload scans 3,028 products with 1,000 children sharing a parent. Both compared rules export the same 1,000 rows.

| Profile | Product predicate | Parent predicate | Extra parent work |
| --- | ---: | ---: | ---: |
| Independent baseline total queries | 64,399 | 70,444 | 6,045 |
| Fixed total queries | 64,399 | 64,413 | 14 |
| Fixed relation queries | 0 | 7 | 7 |
| Fixed product collection queries | 4 | 11 | 7 |

Parent relation queries fell from 3,027 to 7. An unprofiled sequential run took 38.534 seconds for the product predicate and 36.184 seconds for the parent predicate, with 84 MiB peak memory each. Wall time varies with the local environment; the query reduction is the stronger evidence. This does not establish production-scale throughput.

## Repository checks

- PHPUnit: **1,003 tests, 2,811 assertions**, passing.
- JavaScript frontend and CI checks: **79 passed**.
- Magento framework/editor JavaScript checks: **14 passed**.
- PHPStan level 8 on affected source, including `Feed.php`: no errors, no baseline expansion.
- Scoped PHPCS: no source errors or warnings. The installed standard emits its existing deprecation notices.
- Repository validation: 26 XML files, 8 feed types; passed.
- Magento XSD validation: 26 files; passed.
- Wiki validation: 44 pages and sidebar targets; passed.
- `git diff --check`: passed.

Regression tests were observed failing before fixes or with the corresponding fix temporarily reverted. Coverage confirms execution of the actual condition, configuration, save, generator, selection, parent resolver, and observer methods. The full suite covers `Generator::getLogger`; the real queue reproduction verifies handler isolation across feeds.

Primary commands:

```sh
MAGENTO_ROOT=/private/tmp/shopping-feed-functional-20261109/app \
XDEBUG_MODE=coverage /opt/homebrew/opt/php@8.4/bin/php \
/private/tmp/shopping-feed-functional-20261109/app/vendor/bin/phpunit \
-c phpunit.xml.dist --coverage-filter Model --coverage-filter Observer \
--coverage-clover /private/tmp/sho3-fixes-20261110/coverage-final.xml

node --test dev/tests/frontend/*.test.cjs dev/tests/ci/*.test.cjs
MAGENTO_ROOT=/private/tmp/shopping-feed-functional-20261109/app \
node --test dev/tests/product-selection-editor.test.cjs dev/tests/magento-ui-form.test.cjs
```

Evidence is under `/private/tmp/sho3-fixes-20261110/`, including red/green test outputs, coverage, independent scripts and results, controller roundtrips, queue checks, browser output, query profiles, and batch comparison. The disposable runtime was updated by scoped patches to preserve its separate SHO-1 XML work. Temporary browser harness pages were removed after testing. Synthetic feeds and their outputs remain available for inspection; no production, remote repository, or external service was changed.
