# Full catalog generation

Requested November 11, 2026, after the bounded functional acceptance suite. Matt approved Generic XML and Google Shopping XML on both stores.

## Committed implementation

Combined candidate commit: `3deb34d`, branch `feature/sho-1-sho-2-sho-3`, isolated checkout `/private/tmp/shopping-feed-deploy-20261111/candidate`. Author: Matt MacDougall <matt.macdougall@rocketweb.com>. The combined implementation, preview regression, and prior acceptance report/evidence are committed locally as an integration artifact only. Matt clarified that fixes must remain on the existing PR branches: the focused preview fix and combined acceptance evidence are retained on `feature/sho-2-sho-3` for PR #27 as `2bbc075`. PR #26 does not contain the logger-reset regression. Nothing was pushed. Pre-commit PHP suite: 1,066 tests, 3,048 assertions passed.

## Scope and execution

- Alpha WANDS store view 2: new Generic XML feed 21 and Google Shopping XML feed 22.
- Hyvä WANDS store view 3: new Generic XML feed 22 and Google Shopping XML feed 23.
- Each starts with 79,734 eligible top-level products across simple, configurable, bundle, grouped, virtual, and downloadable types. This is the full eligible website catalog, not all 107,815 database rows regardless of website/status/visibility. Associated product expansion changes the final row count.
- Existing tested mappings were copied to separate feed configurations. Conditional outputs use a price predicate and Product Selection uses a nonrestrictive SKU wildcard. Google retains the QA merchant brand/MPN mapping used by the earlier demo test. No provider submission is made.
- No schedules or upload destinations. New output paths use `full_20261111_<feed-id>.xml`; original feeds and outputs are untouched.
- One feed at a time per store, 2,000-product batches, fresh PHP process per batch, 2 GiB PHP limit. The module's process locks coordinate with the normal cron worker. XML is published only after the final batch.
- Backups and manifest: `/opt/comtom/backups/shopping-feed-20261111/full-catalog/{alpha,hyva}/`. Runtime logs/checkpoints: `/opt/comtom/stores/{alpha,hyva}/src/var/shopping-feed-full-20261111/`. A scoped stop/cancel script is prepared; it requires the feed process lock before removing the run's own queue rows and disabling its own feeds.

## Results

All four feeds completed, parsed as complete XML, and downloaded successfully over public HTTPS. Each consumed all 79,734 eligible top-level catalog entries. Parsed output counts equal the persisted exported counters. There are no duplicate or missing IDs, missing conditional values, leftover temporary XML files, or queued jobs.

| Store | Download | Unique rows | Generation time | Bytes |
| --- | --- | ---: | ---: | ---: |
| Alpha | [Generic XML](https://latest.rocketcode.com/media/mageos-shopping-feed/full_20261111_21.xml) | 103,389 | 11m 46s | 20,726,013 |
| Alpha | [Google Shopping XML](https://latest.rocketcode.com/media/mageos-shopping-feed/full_20261111_22.xml) | 103,366 | 41m 20s | 146,886,783 |
| Hyvä | [Generic XML](https://latest-hyva.rocketcode.com/media/mageos-shopping-feed/full_20261111_22.xml) | 103,389 | 12m 02s | 20,726,013 |
| Hyvä | [Google Shopping XML](https://latest-hyva.rocketcode.com/media/mageos-shopping-feed/full_20261111_23.xml) | 103,366 | 41m 50s | 146,023,264 |

The initial simple-product estimates were 13.8–15.0 minutes for Generic and 36.2–37.8 minutes for Google. Measured full runs took 11.8–12.0 and 41.3–41.8 minutes respectively. Google batches near the end expanded composite products into associated rows and took longer than the earlier simple-product batches. Future estimates should budget about 45 minutes per Google run on this catalog and current host, plus verification time; these measurements are not a throughput guarantee. Both stores ran concurrently, with their two feed types sequential within each store.

All Generic rows contain `full catalog verified`. Each Google file contains 103,361 true-branch values and five `review price` fallback values. Those five grouped QA products have an empty raw price attribute but a valid computed Google price from associated products, so the different conditional and price-mapping results are expected.

Google field checks found no empty `id`, `title`, `description`, `link`, `image_link`, `price`, `availability`, `brand`, or `mpn` values; no malformed numeric/currency price strings or non-HTTP(S) link/image strings. Every price is USD. Availability counts are 97,021 in stock and 6,345 out of stock. These are local syntax/completeness checks, not remote validation of 100,000 product/image URLs or provider acceptance.

Both Generic files share SHA256 `c11661859e4646201c7f62af3f472ce68566b425a36583879780f330591388ab`. Google hashes differ between stores, which have different product IDs and URLs. Each public download's byte count and SHA256 matches its own generated file. Exact hashes are in the linked validation evidence.

## Exclusions and diagnostics

Six grouped QA products, `WANDS-QA-GROUPED-01` through `06`, were skipped by Generic because their mapped raw price is empty. Six single-product previews per store reproduced the existing empty-price filter without rebuilding the feeds. Configurable parents are omitted under the associated-only setting, and each run records 62 duplicate-suppression notices from composite handling.

Google logs also identify two backorder rows without a required future availability date, 23 product IDs with an empty image mapping, and one with an empty price mapping. These existing filters and availability validation remain in force. The persisted Google `skipped` counter is five; adapter-level suppression diagnostics are distinct from the mapped-row skip counter, so that counter is not an exhaustive count of every suppressed product. No generation ERROR or CRITICAL records were found. The source data was not changed to force filtered rows into these feeds.

## Preservation and final state

The before/after comparison found no changed pre-existing feed-table rows: 4,033 rows on Alpha and 2,379 on Hyvä, including the earlier bounded QA fixtures. All three original feed output files and both `app/etc/config.php` files remain unchanged. Each store now has 17 bounded QA feeds plus the two requested full feeds, all disabled, with zero schedules, uploads, or queue rows. Both PHP and cron containers are running, maintenance is disabled, and both storefronts return HTTP 200. Published Admin assets still match source in both Admin themes.

The full XML files are retained at the download links above. They are demo/test feeds using the existing QA brand and SKU-as-MPN overrides. No merchant-provider upload or certification was performed. Backups and scoped inverse scripts are retained; full rollback rehearsal remains unperformed.

## Follow-up diagnostic correction

The targeted grouped previews exposed a pre-existing logging error: the composite adapter formatted a mapped SKU with `%d`, producing `product id 0`. The message now uses `product row id %s`, preserving the identifier without changing filtering or feed data. This focused fix belongs to the existing PR #27 branch alongside the preview diagnostics fix.

The regression failed with the actual `product id 0` output before the correction and passed afterward. Clover confirms `checkEmptyColumns()` and the changed message execute. PR #27's full unit suite passes: 1,021 tests, 2,870 assertions. The local combined candidate passes 1,067 tests, 3,055 assertions, including the final type assertion added to the earlier preview regression. Scoped PHPStan and PHPCS pass; PHPCS reports installed deprecated sniffs. The initial coverage invocation used a file rather than a directory filter and was corrected before recording coverage. PHPStan was run serially with the standards fixture to avoid duplicate framework registration and the sandbox's parallel-worker socket restriction.

After both Google jobs completed, the one-line correction was deployed to both stores with the old file and a rollback script retained under each store's `grouped-diagnostics-fix/` backup. PHP syntax and exact source parity passed; PHP was gracefully reloaded. All six grouped previews per store still exported zero rows and counted one skipped row each, now logging the correct SKU. Deployed `Composite.php` SHA256: `f62696cfbe130778f4d98526dfe772f57c8cf82557e13f12b81169e9020bcbc5`. The earlier `Generator.php` fix remains unchanged. Full generation preceded this message-only correction; no output-affecting logic changed and no full feed was regenerated unnecessarily.

## Evidence

- [Alpha XML validation](2026-11-11-full-catalog-evidence/alpha-validation.json), [Hyvä XML validation](2026-11-11-full-catalog-evidence/hyva-validation.json).
- [Alpha public downloads](2026-11-11-full-catalog-evidence/alpha-http-validation.json), [Hyvä public downloads](2026-11-11-full-catalog-evidence/hyva-http-validation.json).
- [Alpha preservation](2026-11-11-full-catalog-evidence/alpha-preservation.json), [Hyvä preservation](2026-11-11-full-catalog-evidence/hyva-preservation.json).
- [Alpha diagnostics](2026-11-11-full-catalog-evidence/alpha-final-diagnostics.json), [Hyvä diagnostics](2026-11-11-full-catalog-evidence/hyva-final-diagnostics.json).
- [Alpha final service state](2026-11-11-full-catalog-evidence/alpha-operational-state.json), [Hyvä final service state](2026-11-11-full-catalog-evidence/hyva-operational-state.json).
- [Regression failure](2026-11-11-full-catalog-evidence/grouped-diagnostic-red.log), [regression pass](2026-11-11-full-catalog-evidence/grouped-diagnostic-green.log), [executed coverage lines](2026-11-11-full-catalog-evidence/grouped-coverage.txt).
- [Standalone PR #27 PHPUnit](2026-11-11-full-catalog-evidence/pr27-phpunit.log), [combined PHPUnit](2026-11-11-full-catalog-evidence/combined-phpunit.log), [PHPStan](2026-11-11-full-catalog-evidence/pr27-phpstan.log), [PHPCS](2026-11-11-full-catalog-evidence/pr27-phpcs.log).

Validation commands included `verify-full-final.sh`, `validate-full.py`, `verify-http.py`, `final-diagnostics.py`, and `operational-state.py` in the remote deployment backup. The XML, HTTP, and preservation check scripts are retained alongside this report for reproducibility. Logs have terminal color escapes and trailing whitespace removed for review.

Fixes and this report remain on the existing `feature/sho-2-sho-3` branch for PR #27. No new PR, push, merge, or release was made. PR #26 remains unchanged; the combined checkout is a local integration artifact.
