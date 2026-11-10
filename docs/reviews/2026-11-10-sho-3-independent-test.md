# SHO-3 independent test: product selection by attribute condition

Date: November 10, 2026. Independent verification of the uncommitted SHO-3 working tree. I tested and reported only; no implementation code was changed, committed, pushed or deployed.

## Verdict

**Not ready to accept as-is.** The core selection semantics held up on real generation. That covers narrowing, independent parent and child rows, selection before duplicate tracking, single-parent matching, missing parents, store-view values, batch resume and SHO-2 coexistence. Two medium defects make failures hard to diagnose or silently lose edits. Six low defects affect counts, wildcard and date semantics, validation coverage and parent-rule cost. Passing unit and JavaScript suites are not the basis of this verdict; every finding below was reproduced through real generation, the installed Admin UI components, or the real Save controller.

| # | Severity | Finding |
|---|---|---|
| F1 | Medium | A stored rule that becomes invalid stops scheduled generation with no reason logged anywhere |
| F2 | Medium (pre-existing code, new exposure) | Editing a rule value to a numerically equal string reports success but is not saved |
| F3 | Low | Skipped counts and skip log lines count evaluations, not products |
| F4 | Low | The `?` wildcard matches one byte, not one character |
| F5 | Low | Date and Time attributes are compared as strings |
| F6 | Low | Save accepts rule shapes and attributes the editor cannot show or edit |
| F7 | Low (programmatic) | Setting the selection to `null` keeps the previous rule |
| F8 | Low (measured) | Parent-scope rules add uncached per-row queries |

## Findings

### F1 (Medium): an invalid stored rule fails generation silently

- **Where:** [Model/Generator.php:310](../../Model/Generator.php:310) validates the selection before [Model/Generator.php:326](../../Model/Generator.php:326) (`updateStatus`) and [Model/Generator.php:329](../../Model/Generator.php:329) (`getLogger()`, which attaches the per-feed log handler at [Model/Generator.php:1066](../../Model/Generator.php:1066)). The exception reaches [Cron/Process.php:143](../../Cron/Process.php:143), which sets Error and calls `$this->logger->error()` on the shared logger before any feed handler exists.
- **Trigger:** save a feed whose rule uses an attribute, then delete or rename that attribute, then let cron generate it. A rule written directly to the config table triggers it the same way.
- **Reproduction:** `sho3-deleted.php setup` (attribute `sho3i_tmp3`, feed 86), then `sho3-deleted.php run` through `Cron\Process`.
- **Expected:** status Error plus the reason in the feed log, as SHO-2 does.
- **Actual:** `{"execute":false,"status":"5","feed_log_delta_tail":""}`; system.log grew 0 bytes; exception.log grew 0 bytes; feed messages `[]`. The same result came from the earlier deleted-attribute run (feed 74) and the malformed stored rule (feed 62, `out/malformed-cron.json`).
- **Control:** the same trigger in a SHO-2 Conditional Value column (`sho2-deleted.php`, feed 85) logs `ERROR: Unknown Conditional Value attribute: sho3i_tmp2.` to the feed log, because SHO-2 validates at map time, after the logger is attached. The difference comes from where SHO-3 validates.
- **Also observed:**
  - When several feeds ran in one PHP process, the error was written into earlier feeds' logs (feeds 54 to 56) instead.
  - Until the rule is fixed, any unrelated save fails with `Unknown Conditional Value attribute: sho3i_tmp.` The editor keeps the deleted attribute visible as its raw code. Removing that row and saving through the Save controller succeeded, so the state is recoverable once someone knows the cause.
- **Impact:** after a routine catalog change, a merchant sees a feed stuck in Error with an empty log and no message. Selection errors also say "Conditional Value" (for example `Invalid Conditional Value operator for price.`), which points at the wrong feature.

### F2 (Medium, pre-existing persistence code): numerically equal edits are dropped

- **Where:** [Model/Feed.php:424](../../Model/Feed.php:424) compares the decoded stored config with the new value using loose `!=`. PHP 8 compares numeric strings inside arrays numerically, so `"00123" == "123"`.
- **Reproduction:** through the real Save controller with the Admin JSON envelope (`roundtrip.php leading-zero`, feed 76):
  1. The feed stores `sku == "00123"`.
  2. Post `sku == "123"`. The result is `success: You saved this feed.`, but the stored value is still `00123`.
  3. Control: posting `sku == "124"` persists.
  - An earlier model-level run showed the same for `"100"` to `"1e2"`.
- **Expected:** the posted rule is stored.
- **Actual:** the old rule stays, with a success message. Reopening shows the old value.
- **Impact:** numeric SKUs with leading zeros, or values like `1.0` and `1`, can never be corrected in place, and the feed keeps exporting the old product set. The comparison predates SHO-3 and affects other config fields, but free-text rule values make it easy to reach.

### F3 (Low): skipped counts count evaluations, not products

- **Where:** [Model/Product/Adapter/AdapterAbstract.php:1061](../../Model/Product/Adapter/AdapterAbstract.php:1061) logs and calls `updateCountSkip()` (line 1078) for every rejected evaluation.
  - It runs for the parent row at [Model/Product/Adapter/Type/Composite.php:235](../../Model/Product/Adapter/Type/Composite.php:235) and again for each associated row at line 305.
  - Local Inventory evaluates again per source at [Model/Product/Processors/LocalInventory.php:144](../../Model/Product/Processors/LocalInventory.php:144) and line 164.
- **Evidence (one feed per process):**
  - Generic feed with a rule: `Processed 30 products (30/30) | Added 2 rows, 37 skipped`, with 37 "product selection conditions did not match." lines. `sho3i-shared` is listed three times.
  - The same catalog without a rule: `Added 35 rows, 0 skipped`, even though four duplicate skips are logged.
  - Google Local Inventory with a rule: `Added 2 rows, 59 skipped` for 30 products.
- **Expected:** counts that can be compared with product counts. [docs/wiki/Filters-and-Transformations.md:83](../wiki/Filters-and-Transformations.md:83) tells users to "compare expected product and skipped counts".
- **Impact:** diagnostics only. Exported rows were correct.

### F4 (Low): `?` matches a byte

- **Where:** [Model/ConditionalValue/Condition.php:96](../../Model/ConditionalValue/Condition.php:96) builds the regex without the `u` flag.
- **Reproduction:** product `sho3i-s-é` with rule `sku glob sho3i-s-?`. Not selected (`functional.txt`, line 19).
- **Expected:** [docs/wiki/Filters-and-Transformations.md:91](../wiki/Filters-and-Transformations.md:91) says `?` matches one character.
- **Impact:** SKUs with non-ASCII characters. `*` is unaffected.

### F5 (Low): Date and Time attributes use string comparison

- **Where:** Magento's `getInputType()` ([vendor/mage-os/module-rule/Model/Condition/Product/AbstractProduct.php:385](/private/tmp/shopping-feed-functional-20261109/app/vendor/mage-os/module-rule/Model/Condition/Product/AbstractProduct.php)) maps only `date` frontend input to `date`, so `datetime` becomes `string`. As a result:
  - The date branch at [Model/ConditionalValue/Condition.php:109](../../Model/ConditionalValue/Condition.php:109) is skipped.
  - The date format validation in `validateConditions` is skipped.
  - The editor definition offers string operators.
- **Reproduction:** `sho3i_dt` (Date and Time input) stores `2026-03-01 15:30:00`. Rule `sho3i_dt == 2026-03-01` does not select it (`functional.txt`, line 41). `>=` works only by lexical comparison.
- **Expected:** [docs/wiki/Filters-and-Transformations.md:89](../wiki/Filters-and-Transformations.md:89) says "Dates use `YYYY-MM-DD`" without excluding Date and Time attributes.
- **Impact:** custom Date and Time attributes. Magento's `news_from_date` (Date input) compared correctly with `==`, `>=` and `<=`.

### F6 (Low): save accepts rules the editor cannot represent

- **Where:**
  - [Model/ConditionalValue/Configuration.php:52](../../Model/ConditionalValue/Configuration.php:52) only checks `is_array($value['conditions'])`, so a keyed object passes.
  - [Model/ConditionalValue/Configuration.php:126](../../Model/ConditionalValue/Configuration.php:126) accepts any existing EAV attribute.
  - The editor builds rows with `(data.conditions || []).map(condition)` at [view/adminhtml/web/js/form/conditional-value.js:84](../../view/adminhtml/web/js/form/conditional-value.js:84).
- **Reproduction, keyed conditions:** `roundtrip.php keyed`, feed 77, posted through the Save controller as `{"aggregator":"any","conditions":{"1":{...},"3":{...}}}`.
  - The save reports success, and the value is stored and projected back as a JSON object.
  - Generation works and exports `sho3i-s-img` and `sho3i-s-noimg`.
  - In the installed editor the field fails to construct: `(data.conditions || []).map is not a function` (browser check "H conditionsObject").
  - The Admin user can then neither see nor fix the rule.
- **Reproduction, hidden attributes:** `tier_price`, `quantity_and_stock_status` and `media_gallery` are accepted on save, although the editor omits them ([Model/ConditionalValue/Condition.php:29](../../Model/ConditionalValue/Condition.php:29)). `tier_price != 1` selected every product. An empty wildcard pattern and a select value that is not an option ID (`Xray`) are also accepted.
- **Expected:** [docs/wiki/Filters-and-Transformations.md:105](../wiki/Filters-and-Transformations.md:105) says saved or imported malformed conditions are rejected. The implementation report says structured tier-price, stock-status and gallery data are not offered.
- **Impact:** requires import, an API client or a crafted request. The Admin editor itself always posts a list of offered attributes.

### F7 (Low, programmatic): `null` does not clear a stored rule

- **Where:** [Model/Feed.php:424](../../Model/Feed.php:424) writes `null` into a `mediumtext NOT NULL` value column, and the update keeps the old value.
- **Reproduction:** after a rule run, `setData('filters_product_conditions', null)` plus save, then generate. The output still reflects the previous rule (`["sho3i-cb-1","sho3i-shared"]`; `functional.txt`, line 70).
- **Control:** the editor's clear value `{"aggregator":"all","conditions":[]}` through the Save controller is stored and disables selection (30 fixture rows exported).
- **Impact:** scripts or integrations that clear with `null`. The Admin path is unaffected.

### F8 (Low, measured): parent-scope rules add uncached per-row queries

- **Where:** [Model/ProductSelection/Selection.php:79](../../Model/ProductSelection/Selection.php:79) calls [Model/ProductSelection/Parents.php:31](../../Model/ProductSelection/Parents.php:31) for every standalone row. That runs one `catalog_product_relation` query, plus a parent collection load with `addAttributeToSelect('*')` (line 48) when parents exist. Nothing is cached, so a shared parent is loaded again for each child.
- **Workload:** 3,001 synthetic visible simples plus the 34 fixtures (3,028 products in the feed). The first 1,000 simples link to one grouped parent through `catalog_product_relation`. Developer mode, PHP 8.4, Xdebug off, CLI opcache off, one generation per process, two rounds:

  | Rule | Rows | Round 1 | Round 2 |
  |---|---|---|---|
  | None | 3,028 | 36.6 s | 31.7 s |
  | Product `sku glob sho3iperf-s-1*` | 1,000 | 31.1 s | 30.3 s |
  | Parent `sku == sho3iperf-grp` (same output as product rule) | 1,000 | 36.3 s | 34.4 s |
  | Any: product wildcard or parent SKU | 2,000 | 35.9 s | 33.5 s |

- **Queries (profiler on):**
  - Product rule: 64,399.
  - Parent rule: 70,444, including 3,027 relation queries and 1,010 extra product collection loads.
  - No rule: 78,582, because it maps three times as many rows.
- **Reading:** on this workload a parent rule costs about 4 to 5 s (14 to 17%) more than a product rule with the same output, and the cost grows linearly with standalone rows. The baseline mapping cost (about 26 queries per product) dominates.
- **Code-based concern, not measured at scale:** the condition definition is 43.8 KB and appears three times in the 251 KB form metadata (selection plus both column parameter editors). It grows with every select option in the catalog.

## Observations (not defects)

- SKU `==` is case-insensitive (Magento comparison) while the SKU wildcard is case-sensitive. The docs mention case only for wildcards.
- Missing attribute values satisfy only "is undefined". For example, Boolean `== No` excludes products with no value.
- Opening a value with an unknown aggregator silently changes the form value to All. Validation stops such a value being stored, so this is only reachable from direct database edits.
- Changing a select option in the editor stores an integer (`2`), while untouched values stay strings (`"2"`). Both save and evaluate the same way.
- The editor's disabled state is defense in depth: a grid-only user is refused by Edit and Save before reaching it.

## Passing checks

**Admin editor:** the installed `product-selection` component, using the real server definition from `Metadata::get()`, the installed static deployment and Magento's Knockout template engine, ran in a real browser. 29 of 32 checks passed:
- Reopening feed 75's stored Any rule showed exact scopes, operators and values, a numeric option ID (`9`, shown as Xray), parent status Disabled (string `"2"` against integer option `2`) and Has base image Yes. Opening wrote no changes.
- Edits reached the form provider: All/Any, product/parent scope, option value, remove, add, the default product scope for a new row, operator reset on attribute change, a new parent SKU wildcard, and "is undefined" hiding the value control. The value carried no output branches.
- The edited rule reopened exactly. Removing every row stored `{"aggregator":"all","conditions":[]}`.
- A deleted attribute stayed selected as its raw code with its operator, and was not rewritten.
- In the disabled state every control was disabled and Add did nothing.
- Integer option IDs selected their labels.
- The 3 failures are malformed stored values (keyed object, string, `null` row). Only the keyed object can pass validation (F6).

**Save controller round trip** (real `Save::execute()` with the `feed_form_data` envelope, built from `FeedFormData::project()` and `redact()`):
- Browser-edited rule: stored and reprojected exactly.
- Integer status value: stored and reprojected.
- Cleared rule: stored as an empty condition list, which disables selection.
- Unknown attribute: rejected with `Unknown Conditional Value attribute: sho3i_missing.`; stored rule unchanged; the invalid input kept in the data persistor for correction.
- Deleted-attribute feed: fixed by removing that row.

**ACL** (one admin user per process; `_isAllowed()` on the real controllers; Metadata's field config):
- Grid-only role: grid, generate and Test allowed; Edit, Save and New denied; editor metadata disabled.
- Administrators: all allowed; editor enabled.

**Generation** (73 checks on actual files, 69 passed): the 4 failures are F4, F5, F7, and one check I wrote wrongly (`?` legitimately matched `[` in `sho3i-s.dot+[x]`; the corrected check passed).
- Every empty selection form (`null`, `''`, `[]`, empty conditions with All or Any) preserved the output byte for byte.
- SKU wildcards: prefix, case sensitivity, literal punctuation and backslash, `!glob`.
- Has base image: blank, whitespace and `no_selection` count as absent; store-view image overrides on stores 1 and 2 respected.
- Multiselect `{}`, `()` and `!{}`; Boolean `0`/`1`, `!=` and `<=>`; price `0`, `<` and `>`; `news_from_date` `==`, `>=` and `<=`; select option IDs as string or integer; store-view text on standalone and associated rows.
- Parent semantics on configurable, grouped and bundle:
  - The whole expression must match one parent; Any is evaluated per parent.
  - Parent values are read in the feed store.
  - Disabled parents count, including one disabled only in the feed store view.
  - A missing parent never matches `!=`, `<=>` or `!glob`, but a product alternative in an Any rule still matches.
- Row independence: a rejected parent row never suppressed matching children (configurable, grouped, bundle), and a parent row can be selected alone.
- A visibility condition does not re-enable hidden standalone products, and a product status Disabled condition selects nothing.
- Selection runs before duplicate tracking: a shared child rejected under `cfg-a` was still exported under `cfg-b`.
- The type filter stays effective; inherited parent values do not satisfy product conditions; inherited output columns remain.

**Validation:** every invalid input was rejected on save with the stored rule preserved:
- Attributes: unknown, uppercase.
- Operators: not offered for the attribute, wildcard on a non-SKU attribute.
- Has base image values `2` and `>`.
- Bad scope.
- Condition count: 51.
- Aggregator: bad, missing, or present without conditions.
- Conditions or values: scalar conditions, array value, `null` value, more than 4,096 bytes.
- Dates: bad date, date with time.
- Whole value: `false`, `true`, an integer, a JSON string.

A malformed stored rule fails generation even with an empty (virtual-only) catalog.

**Preview, reuse and batch:**
- Preview of a selected SKU, a rejected SKU, and a rejected parent showing its child; full generation after preview in the same process.
- Rule changes and the same rule across stores, in reused services.
- Batch resume: 7 batches of 500, each in its own process, with a mixed product and parent rule, produced exactly the 2,002 rows of a single full run, with no duplicates. The file was published only after the last batch and the queue row was removed.

**SHO-2 and destinations:**
- SHO-2 Conditional Value columns, parent conditions and Replace Empty Values combined with selection produced the exact expected rows, and preview matched.
- Generic XML exported only the 3 selected rows.
- Google Shopping exported 1 row; its validators also drop `s-zero` and `ca-1` without a rule.
- Meta and Pinterest exported 0 rows, matching their baselines.
- Google Local Inventory exported the correct 2 rows (counts inflated, F3).

**Suites:** PHPUnit 991 tests and 2,771 assertions OK. Node tests 79 and 13 passed.

## Unverified concerns

- After a thrown generation error, the queue row remained with `is_read=1` (rows 7 and 8, feeds 57 and 62). I did not test whether later cron runs retry or skip such a row.
- The feed Import controller was not tested with a keyed `conditions` object (F6 was reproduced through Save only).
- The metadata payload was not measured on a catalog with large option lists.

## Untested

- A signed-in Admin edit page. Signing in needed the store's admin password, which I did not read. Instead I ran the installed components with the real definition, the server metadata and the real Save controller. A full page with a form key over HTTP, interplay with other fields, and visual layout were not checked in this session.
- `category_ids` and `attribute_set_id` conditions in generation.
- Multiple websites, production mode, other Magento versions, multi-source inventory beyond the Local Inventory format, uploads and schedules, and concurrent workers.
- Catalogs larger than about 3,000 products, and real configurable-heavy catalogs (the performance catalog used relation rows only).
- TikTok, Microsoft and OpenAI destinations.

## Source and runtime tested

- **Checkout:** `/Users/matt/code/module-shopping-feed`, branch `docs/parent-inheritance-how-to`, HEAD `7b8ef88`, plus the uncommitted SHO-3 working tree.
- **Runtime:** `/private/tmp/shopping-feed-functional-20261109/app`, Mage-OS 4.0.0 (Magento 2.4.9 base), PHP 8.4, developer mode, database container `sho-functional-db-20261109`, search container `sho-functional-search-20261109`, Admin at `http://127.0.0.1:8131/admin`. The module is a copy at `vendor/mage-os/module-shopping-feed`.
- **Identity, rechecked at report time:**
  - 21 of the 23 module files compared are byte-identical between checkout and install. They include every SHO-3 file and the form data, Save controller, converter and cron files.
  - The four deployed editor files in `pub/static` (two scripts, two templates) are identical to source.
  - `Model/Generator.php` and `Model/Feed.php` differ. The install has XML output code that this checkout lacks, and its Generator sets the preview SKU without the checkout's test-mode guard. The SHO-3 validation ordering (installed lines 313 and 332) and the loose comparison at `Model/Feed.php:424` are identical in both.

## Environment changes left behind (disposable store only)

- Feeds 49 to 86, all named `SHO3I ...`. Queue rows 7 and 8 (feeds 57 and 62) remain with `is_read=1`.
- Products:
  - 34 fixtures with SKU prefix `sho3i-`.
  - 3,001 SQL-inserted products with prefix `sho3iperf-` (no stock items, URL rewrites or index entries).
  - 1,000 `catalog_product_relation` rows to grouped parent 3041.
- Attributes:
  - Kept: `sho3i_text`, `sho3i_flag`, `sho3i_multi`, `sho3i_dt`, `sho3i_select`.
  - Created and deleted: `sho3i_tmp`, `sho3i_tmp2`, `sho3i_tmp3`.
- Admin role `SHO3I grid only` (3), and users `sho3i_full` (2) and `sho3i_readonly` (3). Their generated passwords exist only in a mode 0600 file in the evidence folder and were never printed.
- Feed logs `var/log/mageos_shopping_feed_<id>.log`, including cross-feed lines in feeds 54 to 56, and 33 `sho3i_*` output files in `pub/media/mageos-shopping-feed/`.
- My browser test page was removed from `pub/static/shopping-feed-tests/sho3i/`. The implementation session's `conditional-value-switch.*` files there were left untouched.
- Database backup taken before my first write: `/private/tmp/sho3i-20261110/db-before-sho3i.sql.gz`.

## Evidence

Everything is in `/private/tmp/sho3i-20261110/`. Each script was inspected before running and writes only to the disposable store.
- `functional.txt`, `functional-results.json`: generation checks.
- `boundaries.txt`, `out/`: validation, logs, destinations and outputs.
- `roundtrip.php`, `roundtrip.txt`: Save controller.
- `acl-one.php`, `acl.txt`: ACL.
- `browser/sho3i-selection.*`: editor harness.
- `sho3-deleted.*`, `sho2-deleted.*`: F1 and its control.
- `perf-*`: F8.
- `batch.*`: batch resume.
- `unit.txt`, `js.txt`, `js-framework.txt`: suites.
