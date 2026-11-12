# SHO-5 per-column modifiers: independent end-to-end test

Date: 2026-11-11. Independent test of the uncommitted SHO-5 working tree on `feature/sho-5-per-column-modifiers`. No implementation code was changed, and nothing was committed, pushed, deployed or published. This report is the only file added to the repository.

## Outcome

SHO-5 is not ready to be called fully verified. The Admin editor, persistence, validation, ordering, the mapping pipeline, batched generation and the no-modifier regression all pass. Three SHO-5 defects of medium severity remain, plus two low-severity usability issues:

1. **Medium.** Escape HTML output is corrupted in unenclosed `;` feeds: `&lt;b&gt;` becomes `&lt b&gt `.
2. **Medium.** An explicit Strip tags keeps `<script>` and `<style>` body text that the default cleaner removes. Page Builder `<style>` blocks leak CSS into the feed.
3. **Medium.** Round emits PHP float text: `-0`, `1.2345678901235E+17`, and only 14 significant digits, so Round 9 or 12 silently drops requested places. The output also changes with the server's `precision` ini setting.
4. **Low.** The Admin accepts padded or nonexistent column names (`" title "`) with no warning. The rule then silently does nothing. The no-effect behavior is documented; the lack of trimming or warning is the issue.
5. **Low.** A stored scalar such as `false`, `true`, `0` or `"abc"` is rejected with "Column modifiers must be a list of at most 200 rows." The row limit in that message is misleading.

Eight focused regression cases cover defects 1 to 3. All eight fail against the unchanged implementation on both PHPUnit runners (Appendix A).

Feeds without modifiers keep their previous output. Five such feeds produced byte-identical files on SHO-5 and on HEAD generation code, including the 3,035-product default catalog.

## Test target

| Item | Value |
| --- | --- |
| Repository | `/Users/matt/code/module-shopping-feed` |
| Branch | `feature/sho-5-per-column-modifiers` |
| HEAD | `2d1e3142a0132f60db2f83de074812603d65cdd3` |
| Tracked diff | `git diff HEAD` sha256 prefix `e25d2219431fcbfb` |
| Source manifest | 838 tracked and untracked files (`.claude/` excluded), manifest sha256 prefix `9f44eaca9984e994` |
| Snapshot | `/private/tmp/sho5i-20261111/snapshot`, copied from the working tree |
| Installed module | `app/vendor/mage-os/module-shopping-feed` in the isolated store; `diff -rq` against the snapshot was clean after every HEAD swap |
| Recheck at the end | The manifest was recomputed over the working tree after testing and still matched. `git diff HEAD` hash unchanged. |

The working tree contains unrelated edits, notably `CONTRIBUTING.md` and earlier review files. They were preserved, and they are part of the manifest only for identification.

## Environment

- **Store.** A disposable copy at `/private/tmp/sho5i-20261111/app`: Mage-OS product-community-edition 4.0.0 (`magento/framework` 103.0.9), developer mode, PHP 8.4.27, m137 Admin theme.
- **Serving.** PHP's built-in server on `127.0.0.1:8151`.
- **Database.** `sho5i_20261111` on the local disposable container `sho-functional-db-20261109`.
- **Browser.** agent-browser, headless Chromium, with real mouse clicks and drags.
- **Isolation.** Every created feed has empty schedules and uploads. Cron was not run. Queue tests used only their own queue row; pre-existing queue rows for feeds 17, 57 and 62 were untouched. No feed was delivered anywhere.
- **Configuration change.** One change was made, in the isolated database only: `currency/options/allow` went from `USD` to `USD,EUR` for the EUR price tests.
- **Fixtures.**
  - New simple products: `sho5i-a` (3042, Unicode title and markup description), `sho5i-b` (3043), `sho5i-c` (3044).
  - `sho5i-opt` (3045), with a required Size custom option (S, L).
  - Existing composite fixtures from the SHO-3 test store: configurable `sho3i-cfg-a` with shared child `sho3i-shared`, grouped `sho3i-grp`, bundle `sho3i-bun`.
  - Feeds 147 to 173, all named `SHO5I ...`.
- **Harness.** Scripts are in `/private/tmp/sho5i-20261111/`, and evidence is in its `evidence/` directory. Both are retained until cleanup is approved.

## Results matrix

### Admin editor (UI Component form, feed 148)

| Check | Result | Evidence |
| --- | --- | --- |
| Empty state renders the Column, Modifier, Value and Action headers and an Add button | Pass | `shots/01-empty-modifiers.png` |
| Add six rows with interleaved columns. Literal values: `Brand: `, `0`, ` ${x} & "q" é `, three spaces only, `'apos' <b>&amp; ü `. Saved with the real Save button. | Pass | `editor-presave-provider.json`, `shots/02-filled-before-save.png` |
| A modifier targets `promo`, a Static Value column added in the same unsaved form | Pass: column and modifiers stored together | `editor-save1-db.json` |
| Stored configuration after save: the DB row is exact, including spaces, quotes, `&`, `${x}` and Unicode | Pass | `editor-save1-db.json` |
| Reopen: DOM fields and provider data equal the DB, in order | Pass | `editor-reopen1.json`, `shots/03-reopened.png` |
| Edit `0` to `0.0`, drag `title uppercase` above `title prepend`, delete a row, save and reopen | Pass | `editor-save2-db.json`, `editor-reopen2.json` |
| Order of operations: Test Feed shows `Brand: ÉLAN ÉCOLE STRASSE ...` (uppercase, then prepend) and the `promo` literals | Pass | `editor-testfeed-148.txt`, `shots/04-admin-testfeed-148.png` |
| Delete the final row and save: stored value is `[]` | Pass | `editor-save7-cleared-db.txt`, `shots/05-all-deleted.png` |
| Invalid server-side values are rejected with the exact message: Decimal `13`, Round `-1`, Decimal ` 2`, Round `2.0`, Round `abc`, Price `ABCD`, Price `usd` | Pass | `editor-invalid.txt` |
| A blank column is blocked client-side with "This is a required field." | Pass | `editor-invalid.txt` |
| After a rejected save, nothing is persisted (feed name and modifiers unchanged), and the redirected form restores the unsaved name and rows | Pass | `editor-invalid.txt` and the restored-form check |
| Read-only Admin role: the edit page says "Sorry, you need permissions to view this content."; a save POST with a valid key and form key returns 403; DB unchanged | Pass | transcript evidence; DB checked before and after |
| Console: no page errors. All 197 network requests succeeded. | Pass, sampled at form load and after the save sequence rather than continuously | agent-browser console and network logs |
| Padded column `" title "` is saved as is, and Test Feed shows no effect | Fail, defect 4 | `editor-save6-padded-db.json` |

The stale server message from the previous rejection stays visible next to a later client-side error. This is standard Magento message behavior, not SHO-5.

### Every modifier, verified on generated output

The matrix has 121 cases. Each case is a Static Value column on `sho5i-b` with an explicit expected value, read from the raw generated TSV. 114 pass.

| Modifier | Result | Notes |
| --- | --- | --- |
| Prepend, Append | Pass | Spaces, `0`, an empty source (`P::A`), literal `${x} {{name}}`, quotes, `é`, and order across interleaved rows |
| Lowercase, Uppercase, Title case | Pass | Greek, Turkish `İ`, `ß` to `SS`, `ǆ` titlecase to `ǅ`, CJK, Arabic, apostrophes |
| Round | 4 fail | Defect 3. Half away from zero, the `1.005` and `2.675` cases, the suffix kept, no padding, and non-numeric input left unchanged all pass. |
| Integer | 1 documented limitation | `-0.5` gives `0` and `1e20` gives `100000000000000000000`. `12345678901234567` gives `...568`; the float limit is documented. |
| Decimal places | 1 documented limitation | Padding and rounding pass. Decimal 12 on `1234567.123456789` gives `1234567.123456788948`; float noise is documented. |
| Date format | Pass | Offsets kept, `Z`, epoch, escaped literals. Impossible dates (`02-30`, `13`, `25:00`, `:60`, `+25:00`), relative phrases and zero dates stay unchanged. |
| Price format | Pass | Feed currency, suffix kept, relabel, JPY, negative, large values. Blank, `call us`, `1,234.50`, `$12.34` and `12.345 usd` stay unchanged. |
| Strip tags | 2 fail | Defect 2. Entities kept, `a < b > c` kept, comments removed. |
| Escape HTML | Pass in TSV | No double encoding. Bogus entities are encoded. Strip then escape and escape then strip both give the documented order. Fails in `;` feeds: defect 1. |
| Secure URL | Pass | 21 cases: case-insensitive scheme, `//`, port, userinfo, and query or fragment URLs. `ftp:`, `mailto:`, `javascript:`, relative paths, `http://`, `http:///path`, embedded and backslash forms stay unchanged. |

Numeric operations never turn blank, non-numeric or non-finite input into zero. Blank, `abc`, `n/a`, `ten`, `1,234.5`, `0x1A` and `1e999` are all returned unchanged.

**Floating-point behavior.** Round, Integer and Decimal use PHP floats, as the wiki says. Beyond defect 3:

- Integer above 2^53 and Decimal 12 on a seven-digit value show binary noise.
- An unclosed `<` makes Strip tags drop the rest of the value: `keep <br unclosed` becomes `keep `. This is PHP `strip_tags` behavior and is not documented.

**Built-in price rounding.** The price directive has already rounded to two places and converted to the feed currency before the chain runs. Results from `price-currency-results.json` (USD feed 151) and `price-currency-eur-results.json` (EUR feed 153):

- On USD feed 151, base `12.345` gives `12.35 USD`, and Round 0 on that gives `12 USD`, rounded from `12.35`.
- On EUR feed 153, the directive gives `8.72 EUR`, and Round 0 gives `9 EUR`.
- Price format on the raw `price` attribute gives `12.35 EUR`: an unconverted base amount labeled with the feed currency.
- Price format `GBP` on the converted EUR directive gives `8.72 GBP`.

Both labels match the documented "formats the amount; it does not convert currencies". They are easy to misuse, and the Admin gives no warning.

### Mapping and output pipeline

Feeds 154 and 155 are a differential pair: one configuration, run without and with 13 modifiers. They cover:

- direct, static and conditional values (both branches: 11 HIGH and 3 LOW)
- replace-empty fallback
- configurable, grouped and bundle parents and their associated rows
- inherited values from the parent
- a custom option on a product (two option rows)
- `rep`, a column repeated four times
- find and replace, and limits including a Unicode limit

| Check | Result | Evidence |
| --- | --- | --- |
| Headers and row counts identical with and without modifiers. Every cell equals the no-modifier cell with its modifiers applied exactly once (non-idempotent markers `#`, `!`, `~`, `[`, `]`). | Pass, 236/236 | `pipeline-compare-results.json`, `pipeline-mod.json`, `pipeline-nomod.json` |
| Repeated columns: each of the four `rep` cells gets `#` once | Pass | same |
| Find and replace runs before modifiers: `aXa` becomes `bXb`, then `bXba` | Pass | same |
| The limit runs after the chain: `abcdefgh` with prepend `XY` and limit 5 gives `XYabc`. Unicode limit 4 on `éééééé` with append `ü` gives `éééé`. | Pass | same |
| An explicit markup operation bypasses default cleaning; other columns keep the default (`<p>Bold &amp; <b>brave</b></p><script>x()</script>` with append `!` gives `Bold & brave!`) | Pass | `modifier-matrix-results.json` |
| Empty-column exclusion: a prepend fills an empty required column and keeps the product (2 rows; 0 without the modifier). Strip tags that empties a required column skips the product. | Pass | `misc-skipfill*-results.json`, `misc-skipempty-results.json` |
| Separators and newlines in arguments do not break rows in TSV, CSV, pipe, multi-character `\|\|`, or TSV with an enclosure. The argument was `a,b;c\|d<TAB>e<LF>f<CR>g"h'i é &amp; <b> x\|\|y`, plus a prepend of `"q"<LF>`. | Pass, 11/11 each | `format-*-results.json`, `format-*.raw` |
| The same in `;` feeds: the rows are intact, but the escaped column is corrupted | Fail (9/11), defect 1 | `format-semi-results.json`, `format-semi.raw` |
| ISO-8859-1 output with modifiers: uppercase and lowercase on Latin-1 text, a limit counting characters after the chain (`üéé`) | Pass, 5/6 | `misc-iso-results.json`, `iso.raw` |
| ISO-8859-1 lowercase of entity-decoded text: `&Eacute;T&Eacute;` gives `ãtã` | Fail, pre-existing root cause (see below) | `misc-iso-results.json` |
| Preview (Test Feed) against the generated file, value by value | 19/21 with modifiers and 6/8 without. The two mismatches are pre-existing and identical on HEAD. | `preview-155-results.json`, `preview-154-results.json`, `preview-154-head-results.json` |
| Full against batched generation: feed 155 through the Queue batch API, limit 2 | Pass, byte-identical | `full-155.txt`, `batched-155.txt` |
| Full default catalog with modifiers (3,035 products, five modifiers on four columns) compared cell by cell with the same feed without modifiers | Pass: 51,595/51,595 cells | `fullcat-mod-results.json`, `fullcat-mod.txt` |
| Feeds without modifiers keep their previous output: feeds 147 (no config row), 154, 163 and 169 (`[]`, ISO-8859-1), and 166 (full catalog, no row) generated with HEAD `Filter.php` and `AdapterAbstract.php` | Pass, all five byte-identical, 422,890 bytes for 166 | `regen-sho5-*.txt`, `regen-head-*.txt`, `regen-head.txt` |

### Validation boundaries

| Check | Result | Evidence |
| --- | --- | --- |
| Programmatic model save of 48 invalid shapes, run as both an update and a create. Invalid shapes: scalars, JSON strings, associative or non-zero keys, null or scalar rows, missing or blank or non-string columns, unsupported or mixed-case operations, non-string values, a 4097-byte value, a 256-byte column, 201 rows, bad precision, date and currency arguments, and one bad row among good rows. | Pass. Each is rejected with a LocalizedException. Updates leave the feed row and config unchanged, and creates leave no feed row. | `validation-results.json` |
| Valid boundaries: 200 rows, a 4096-byte value, a 255-byte column, null or missing value, integer Round 2, Round 12, blank Price, `[]`, null stored as `[]` | Pass | same |
| Exact-string persistence for values PHP compares loosely: `10`, `1e1`, `10.0`, `010`, `0`, `''`, `0.0`, spaces, `abc` and `ABC`, NBSP, `true`, `null` | Pass, all 15 | same |
| Stored `''` | Accepted and treated as no modifiers. My expectation (`""`) was wrong; this is a harness error. | same, 178/179 |
| Clone (MassClone copier) copies modifiers byte-exactly | Pass | same |
| Export and import round trip | Not applicable. The module has no feed export or import: `feed/import` renders an empty page and its grid button is commented out. | `Controller/Adminhtml/Feed/Import.php`, `Block/Adminhtml/Feed.php:70` |

**Malformed stored configuration at generation.** These cases write the database row directly, bypassing save validation; results are in `malformed-results.jsonl`.

| Check | Result |
| --- | --- |
| `false`, `true`, `0`, `null`, `"abc"`, `not json`, broken JSON, an associative object, an unsupported operation, Round 99, `[null]`, and an unsaved in-memory boolean `false` | Pass, fails closed. Generation throws a LocalizedException before writing, and the previous file is kept byte for byte. |
| Stored `[]` and `''` | Generate normally |
| Real CLI path, `bin/magento mage-os:shopping-feed:generate 172` with stored `false` | Exit 1, "Generation failed.", feed status 5 (Error), and the feed log has `ERROR: Column modifiers must be a list of at most 200 rows.` File unchanged (`cli-malformed-false.txt`). |

My direct `Generator::run()` calls left status 3 (Processing). In production, `Cron\Process` sets Error, so the Processing status comes from the harness, not the implementation. The message for scalar values is defect 5.

### Automated checks

| Check | Result | Evidence |
| --- | --- | --- |
| PHPUnit 12.5.39 on PHP 8.4.27 (mageos-latest) | 1077 tests, 2945 assertions, OK | `unit12.txt` |
| PHPUnit 9.6.34 on PHP 8.3 (mageosdemo) | 1077 tests, 2558 assertions, OK | `unit9.txt` |
| `node --test dev/tests/frontend/*.test.cjs dev/tests/ci/*.test.cjs` | 79/79 | `node.txt` |
| Magento UI form test with `MAGENTO_ROOT` | 14/14 | `node-magento.txt` |
| `composer validate`, Magento XML (26 files), wiki (46 pages) | Pass | `composer-validate.txt`, `validate-xml.txt`, `validate-wiki.txt` |
| `php -l` on PHP 8.1 and 8.4 over 569 PHP and PHTML files | Pass, no output | `lint-8.1.err`, `lint-8.4.err` |
| PHPCS (module standard), scoped to the changed files and over the full module | Pass, exit 0 | `phpcs-scoped.txt`, `phpcs-full.txt` |
| PHPStan 2.3.1 level 8 with the repository's `phpstan.neon.dist` and baseline, on a copy of the snapshot | `[OK] No errors` | `phpstan-full.txt` |
| `dev/tests/check-phpstan-baseline.php --git-ref=origin/main` | "The PHPStan baseline did not grow." (1714 entries became 1713) | `phpstan-baseline-check.txt` |
| Consolidation validator on the clean snapshot, which avoids the nested `.claude/worktrees` tree | Pass: 26 XML files, 8 feed types | `validate.txt` |

A first PHPStan run with my own scoped configuration reported 11 "unknown class" and factory-signature errors in `AdapterAbstract.php` and `Metadata.php`. Its autoloader resolved the module namespace to the standards checkout, and bitexpert generated virtual factories, so this was a harness failure. The project configuration reports no errors (`phpstan-scoped.txt` and `phpstan-scoped.neon` are kept for the record).

## Defects

### 1. Escape HTML output is corrupted in unenclosed `;` feeds (Medium)

- **Source.** `Model/Product/Filter.php:182-192`, `finishModifiedValue()`. When the delimiter is not enclosed (anything but generic `,` without `enclose_cell`), every delimiter in the final value becomes a space. Escape HTML (`Model/Product/ModifierChain.php:95`) creates `;` in every entity, so for `;` feeds the entities are destroyed after the chain.
- **Repro.** A generic feed with delimiter `;` and no enclosure. A Static Value column `esc` = `<b>"Q" & Co, Ltd</b>`, with modifier Escape HTML.
- **Expected.** `&lt;b&gt;&quot;Q&quot; &amp; Co, Ltd&lt;/b&gt;`, or the configuration rejected at save. The wiki says "Escape HTML produces literal HTML entities in the output value."
- **Actual.** `&lt b&gt &quot Q&quot  &amp  Co, Ltd&lt /b&gt ` (`format-semi.raw`).
- **Scope.** The same applies to any custom delimiter that appears in entity text, for example `&` or `#`. TSV, CSV, pipe, `||` and enclosed feeds are unaffected.
- **Regression test.** `testEscapeHtmlInUnenclosedSemicolonFeedNeverEmitsBrokenEntities` accepts either a fix or a save-time rejection.

### 2. Explicit Strip tags keeps script and style bodies (Medium)

- **Source.** `Model/Product/Filter.php:102-117`: any explicit `strip_tags` or `escape` skips `removeMarkup()` and entity decoding. `Model/Product/ModifierChain.php:93` then applies PHP `strip_tags()`, which removes tags but keeps their contents.
- **Repro.** A column whose source is `<script>alert(1)</script>tail` or `<style>p{color:red}</style>x`, with modifier Strip tags.
- **Expected.** `tail` and `x`. Default cleaning of the same column, without the modifier, gives these values.
- **Actual.** `alert(1)tail` and `p{color:red}x`.
- **Impact.** Adding Strip tags makes a column dirtier than leaving it alone. The comment at `Filter.php:105` already notes Page Builder HTML; Page Builder descriptions routinely carry `<style>` blocks, and that CSS would reach the recipient.
- **Wiki.** "Removes literal HTML tags while retaining their text" does not warn that this means script and style source.
- **Regression test.** `testExplicitStripTagsMatchesDefaultCleaningForScriptAndStyle` (2 cases).

### 3. Round returns PHP float-to-string text (Medium)

- **Source.** `Model/Product/ModifierChain.php:134`, `(string)round($number, (int)$argument, PHP_ROUND_HALF_UP)`.
- **Repro and actual results.**
  - Round 0 on `-0.4` gives `-0`; Round 1 on `-0.04 USD` gives `-0 USD`.
  - Round 0 on `123456789012345678` gives `1.2345678901235E+17`.
  - Round 9 on `123456.123456789` gives `123456.12345679`.
  - Round 12 on `1234.123456789012` gives `1234.123456789`.
- **Expected.** Plain decimal text without a negative zero or exponent, keeping up to the requested places, as Decimal places does with `number_format`.
- **Environment dependence.** The result depends on `precision`. With `-d precision=17`, Round 9 gives `123456.12345678901`, more places than requested. With `-1` it gives `123456.123456789`. The wiki promises "Decimal precision from 0 to 12" and mentions only floating point in general.
- **Regression test.** `testRoundReturnsPlainDecimalText` (5 cases).

### 4. Padded or nonexistent column names are accepted silently (Low)

- **Source.** `Observer/ValidateColumnModifiers.php` and `ModifierChain::compile()` validate shape only. The chain lookup in `Filter.php:102` and `modifyRows()` is an exact string match.
- **Repro.** In the Admin, a row with column `" title "` and Lowercase. Save, then Test Feed.
- **Actual.** Stored as `" title "` (`editor-save6-padded-db.json`), with no effect and no message. A mistyped `sale_price_datsale_price_date` was also accepted (`editor-save4-db.json`).
- **Expected.** Trim, or warn when no output column matches. The wiki documents "A name absent from the output has no effect", so this is a usability gap rather than a contract break.

### 5. Misleading message for scalar stored values (Low)

- **Source.** `Model/Product/ModifierChain.php:31`.
- **Actual.** Stored `false`, `true`, `0`, `null` and strings all report "Column modifiers must be a list of at most 200 rows."
- **Expected.** The value is rejected correctly, but the message points at the row limit when the problem is the type.

## Pre-existing observations (not SHO-5)

Each one is identical on HEAD generation code, or lives in files SHO-5 does not change.

- **Custom-option rows have 13 cells under a 15-column header.** The repeated `rep` column collapses and later cells shift left: `3045-option0` in `regen-sho5-154.txt`. Byte-identical on HEAD.
- **ISO-8859-1 output has three problems.**
  - It starts with a UTF-8 BOM.
  - An unmodified limit of 3 on `ééééé` gives `???`.
  - Entity decoding inserts UTF-8 bytes, so `&Eacute;T&Eacute;` is written as `ÃTÃ`.

  Feed 169 is byte-identical on HEAD. SHO-5's lowercase then turns `ÃTÃ` into `ãtã`; the root cause is the pre-existing decode.
- **Preview against full generation differs for shared children.** For a shared configurable child (27) and a grouped child (36), the preview inherits the parent name. The full file emits the child's own name, because the child is first written as a standalone row and later deduplicated. Identical on HEAD (`preview-154-head-results.json`).
- **Two feeds with different currencies in one PHP process share the first feed's currency.** `Feed::getStore()` reuses `current_currency` (`Model/Feed.php:836-844`). The default price directive is affected too (`price-currency-results.json`, feed 152). Cron handles one queue item per run. Run separately, EUR feed 153 passes 8/8.

## Full catalog with modifiers

Feed 173 copies feed 166's columns, skip rules and stock setting. It adds five modifiers:

- `title`: Uppercase, then Prepend `B: `
- `sku`: Append `#`
- `link`: Secure URL
- `description`: Append ` END`

The run completed in 51.6 s and produced 3,035 product rows. Headers and the row count equal the no-modifier file. All 51,595 cells equal the no-modifier cell, with the expected modifier applied exactly once on the four modified columns and no change anywhere else (`fullcat-mod-results.json`).

## Not verified

- **Real cron schedule.** Cron, schedules and uploads were not run, as the brief requires. The Queue batch API, the CLI generation path by feed ID, and the Admin Test Feed were exercised.
- **Preset feed types.** Generation with modifiers ran on generic feeds only. The seven preset types share the delimited writer but were not generated with modifiers. There is no XML output on this branch, and the SHO-1 XML branch was not merged, as the brief requires.
- **Other PHP versions.** Generation-level modifier output ran on PHP 8.4 only. The unit suite ran on 8.3 and 8.4, and syntax checks on 8.1 and 8.4. PHP 8.1, 8.2 and 8.5 runtimes were not run. Rounding edge cases can differ between PHP versions.
- **Admin coverage limits.**
  - Only headless Chromium was used.
  - Keyboard-only reordering, accessibility, and a store-view scope switcher on the edit form were not tested.
  - The console was sampled, not watched continuously.
  - Admin rejection was exercised for the cases listed above. The remaining invalid shapes were covered by programmatic saves, not browser POSTs.
- **Scale.** 200 rows on the full catalog was not timed. The 200-row limit was tested by save only.
- **Recipients.** No external recipient or provider acceptance was tested.

## Cleanup status

Nothing in the shared store, existing catalog or repository was changed, apart from this report. The isolated resources are still in place, so the evidence can be inspected. Once the evidence is no longer needed, removal is:

1. **Done.** The isolated web server on `127.0.0.1:8151` was stopped, and nothing listens on the port. Agent-browser sessions `s5` and `s6` were closed.
2. **Pending approval.** Drop database `sho5i_20261111` and revoke its grant on container `sho-functional-db-20261109`. This also removes feeds 147 to 173, products 3042 to 3045, and the `currency/options/allow` change.
3. **Pending approval.** Delete `/private/tmp/sho5i-20261111`, which holds the app copy, snapshot, PHPStan copy, scripts and evidence. `stan/vendor` there is a symlink to the standards checkout's `vendor`. `rm -rf` removes the link, not its target.

No queue row, schedule, cron job or upload was created outside the isolated database.

## Appendix A: regression tests

The file is `/private/tmp/sho5i-20261111/regression/Sho5IndependentRegressionTest.php`. It is not added to the module suite, so the working tree's suite stays green. It can be copied into `Test/Unit/Model/Product/` with the fix.

Run against the unchanged snapshot:

- PHPUnit 12.5.39 on PHP 8.4.27: `Tests: 8, Assertions: 11, Failures: 8` (`regression-tests.txt`).
- PHPUnit 9.6.34 on PHP 8.3: `Tests: 8, Assertions: 11, Failures: 8` (`regression-tests-php83.txt`).

```php
<?php

declare(strict_types=1);

namespace MageOS\ShoppingFeed\Test\Unit\Model\Product;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use MageOS\ShoppingFeed\Model\Feed;
use MageOS\ShoppingFeed\Model\Generator\Cache;
use MageOS\ShoppingFeed\Model\Product\Filter;
use MageOS\ShoppingFeed\Model\Product\ModifierChain;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Regression tests for defects found by the SHO-5 independent test (2026-11-11).
 * They are expected to FAIL against the implementation as tested; they are not part of the module suite.
 */
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class Sho5IndependentRegressionTest extends TestCase
{
    /** @dataProvider markupWithBodies */
    #[DataProvider('markupWithBodies')]
    public function testExplicitStripTagsMatchesDefaultCleaningForScriptAndStyle(string $input, string $expected): void
    {
        $filter = $this->filter([['column' => 'plain', 'operation' => 'strip_tags', 'value' => '']]);
        $default = $filter->cleanField($input, ['column' => 'legacy']);
        self::assertSame($expected, $default, 'precondition: default cleaning output');
        $row = ['legacy' => $default, 'plain' => $filter->cleanField($input, ['column' => 'plain'])];
        self::assertSame($default, $filter->modifyRows([$row])[0]['plain']);
    }

    /** @return array<string, array<string>> */
    public static function markupWithBodies(): array
    {
        return [
            'script body' => ['<script>alert(1)</script>tail', 'tail'],
            'style body' => ['<style>p{color:red}</style>x', 'x'],
        ];
    }

    public function testEscapeHtmlInUnenclosedSemicolonFeedNeverEmitsBrokenEntities(): void
    {
        $config = ['output_params_delimiter' => ';', 'output_params_enclose_cell' => ''];
        try {
            $filter = $this->filter([['column' => 'e', 'operation' => 'escape', 'value' => '']], $config);
            $value = (string)$filter->modifyRows([['e' => '<b>x</b> & y']])[0]['e'];
        } catch (LocalizedException) {
            // Rejecting Escape HTML for a feed that cannot carry ';' is an acceptable fix.
            $this->addToAssertionCount(1);
            return;
        }
        self::assertStringNotContainsString(';', $value, 'the delimiter must not leak into an unenclosed cell');
        self::assertDoesNotMatchRegularExpression('/&(?:lt|gt|amp|quot|#0?39)(?!;)/', $value, 'broken entity: ' . $value);
    }

    /** @dataProvider roundings */
    #[DataProvider('roundings')]
    public function testRoundReturnsPlainDecimalText(string $places, string $input, string $expected): void
    {
        $modifier = new ModifierChain();
        $chain = $modifier->compile([['column' => 'x', 'operation' => 'round', 'value' => $places]])['x'];
        self::assertSame($expected, $modifier->apply($input, $chain, 'USD'));
    }

    /** @return array<string, array<string>> */
    public static function roundings(): array
    {
        return [
            'no negative zero' => ['0', '-0.4', '0'],
            'no negative zero with suffix' => ['1', '-0.04 USD', '0 USD'],
            'no exponent for large values' => ['0', '123456789012345678', '123456789012345680'],
            'keeps requested digits' => ['9', '123456.123456789', '123456.123456789'],
            'keeps 12 requested places' => ['12', '1234.123456789012', '1234.123456789012'],
        ];
    }

    /**
     * @param array<int, array<string, string>> $rows
     * @param array<string, mixed> $config
     */
    private function filter(array $rows, array $config = []): Filter
    {
        $feed = $this->getMockBuilder(Feed::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $feed->setData(['id' => 1, 'type' => 'generic', 'config' => new DataObject([ModifierChain::CONFIG_KEY => $rows] + $config)]);
        return (new Filter(new Cache()))->setFeed($feed);
    }
}
```
