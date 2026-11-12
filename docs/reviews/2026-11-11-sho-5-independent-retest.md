# SHO-5 fixes: independent retest

Date: 2026-11-11. Branch `feature/sho-5-per-column-modifiers`, HEAD `2d1e3142a0132f60db2f83de074812603d65cdd3`, tested as the uncommitted working tree. Subject: the five changes in [2026-11-11-sho-5-independent-fixes.md](2026-11-11-sho-5-independent-fixes.md), plus the acceptance that document left open. Earlier reports: [independent test](2026-11-11-sho-5-independent-test.md), [modifiers](2026-11-11-sho-5-modifiers.md).

Harness and raw evidence: `/private/tmp/sho5r-20261111/` (scripts at the top level, results under `evidence/`). Nothing in the repository was changed except this report. Nothing was committed, pushed, deployed, published or sent.

## Verdict

**One SHO-5 blocker remains.** Fix 1 (Escape HTML with entity-conflicting delimiters) is incomplete. A multi-character delimiter that mixes an entity character with any other character, such as `; ` (semicolon space), passes save and generation validation. The delimiter-to-space normalization then cuts escaped entities apart, so `&quot;Q&quot; &amp; Co` becomes `&quot;Q&quot &amp Co`. The output is silently corrupt. The wiki states that "save and generation reject unsafe configurations". Its advice to choose a delimiter with a character outside the entity alphabet leads directly to this case. Reproduced through the Admin form on an installed store and in a failing regression test.

Fixes 2 to 5 hold. Three new non-blocking findings:

- B: an accepted modifier on Google's legacy `promotions_id` column never runs.
- C: explicit Strip tags joins words that the default cleaner keeps apart.
- D: modifier rows cannot be reordered from the keyboard.

The remaining acceptance from the fixes document is now covered on an installed store:

- seven presets with modifiers;
- a due schedule through a manual cron run with uploads disabled;
- the full-catalog differential;
- full and batched generation;
- pre-feature output identity;
- complete PHPStan.

All pass apart from the findings above. XML output and real recipient delivery were not tested and are not claimed. The feature cannot be called verified while Defect A is open.

## Identity and drift

| Item | Observation |
|---|---|
| HEAD | `2d1e3142a0132f60db2f83de074812603d65cdd3` on `feature/sho-5-per-column-modifiers`, unchanged throughout. |
| Working tree | 25 status entries (12 modified, 13 untracked); `git diff --stat`: 12 files, 241 insertions, 20 deletions. Untracked implementation under test: `Model/Product/ModifierChain.php`, `Observer/ValidateColumnModifiers.php`; untracked tests: `ColumnModifiersTest`, `ModifierChainTest`, `ModifierRegressionTest`, `ModifierValidationTest`. |
| Source manifest | SHA-256 of 842 working-tree files taken at the start (`evidence/source-manifest.sha256`). Rechecked at 19:25 and at the end: all 842 OK. No drift while testing. |
| Installed module | A snapshot of the working tree was installed into a fresh Mage-OS 4.0.0 project as `vendor/mage-os/module-shopping-feed`. `diff -rq snapshot installed` was empty at the start, after each pre-feature swap and at the end. The snapshot equals the working tree, excluding `.git`, `.claude` and `vendor`. |
| Cron instance | `app-cron` is an APFS clone of the retest app with only `env.php` pointed at a separate database; its module copy also equals the snapshot. |
| Tested claims | The five changes and the "Remaining acceptance" list in the fixes document. |

## Environment

- Mage-OS 4.0.0 (`magento/framework` 103.0.9), developer mode, PHP 8.4.27, built-in server on `127.0.0.1:8152`. Store timezone America/Indiana/Indianapolis.
- MySQL 8.4.11 in container `sho-functional-db-20261109` (port 33087). Retest database `sho5r_20261111`, seeded from a `mysqldump` of `sho_functional`, which was only read. Disposable cron database `sho5r_cron_20261111`, seeded from the retest database. `sho1i_20261110` and `sho5i_20261111` were not touched.
- The harness bootstrap refuses to run unless Magento's root is the retest clone and the connection is `sho5r_20261111`. The cron bootstrap has the same guard for the cron clone and database.
- Unit suites: PHPUnit 12.5.39 on PHP 8.4.27 and PHPUnit 9.6.34 on PHP 8.3. PHPStan 2.3.1, level 8, with the module's baseline.
- Fixtures: the catalog inherited from `sho_functional` (3,035 feed rows, including the SHO-3 performance set), synthetic products 3043 to 3045 (entity text, an option tee), and feeds 147 to 220 named `SHO5R ...`.

## Disposition of the five fixes

| # | Change | Disposition | Key evidence |
|---|---|---|---|
| 1 | Reject Escape HTML with unenclosed entity-alphabet delimiters | **Partial. Not accepted.** Every single-alphabet delimiter is rejected at save and generation, and enclosed or safe formats keep entities intact. Mixed multi-character delimiters are accepted and corrupt output (Defect A). | Rejections 21/21; Admin rejections 6/6 with the database unchanged; enclosed and safe formats 128/128; mixed delimiters 37/45; Admin `; ` save accepted and output corrupt. |
| 2 | Strip tags removes script and style bodies | **Fixed** for the stated scope. New finding C: words across block tags, `<br>` and comments are joined. | Strip checks 14/17: 11 pass, 1 is the documented unfinished-tag behavior, 2 are Finding C. |
| 3 | Round as plain decimal text independent of `precision` | **Fixed** within documented float limits. | Generated feeds at `precision` 3, 14, 17 and -1 and on PHP 8.5: 54/55 each, the only miss being the documented float-limit case. Standalone: 92/92 on PHP 8.1, 8.2, 8.3, 8.4 and 8.5. |
| 4 | Reject unknown modifier columns on save | **Fixed** for exact, padded, case-changed and misspelled names; same-save add, rename and remove work. New Defect B for Google's runtime column rename. | Columns 33/35 (both misses are Defect B); Admin rejections with the exact name, database unchanged, repair succeeds. |
| 5 | Distinct validation messages | **Fixed.** | 8/8 Admin POSTs and 12/12 model-level saves (scalar, integer, `true`, `false`, associative, non-sequential, 201 rows, 201 associative rows, 200 rows, empty list, `null`, `''`) give the specified message or save. |

## Expected vs actual

### Fix 1: Escape HTML and delimiters

| Case | Expected | Actual | Result |
|---|---|---|---|
| New feed: semicolon preset, `other` with `;` `&` `#` `amp` `39` `&#` `;;` `quot;` `x`, Google with `;` | Rejected with guidance, nothing persisted | Rejected with "Escape HTML requires a cell enclosure when the delimiter contains only letters, digits, &, # or ;. Set a cell enclosure or choose another delimiter."; no rows persisted | Pass |
| Existing feed: remove enclosure, switch to `&`, add Escape to an unenclosed `;` feed | Rejected, stored config unchanged | Rejected, unchanged | Pass |
| Unsafe config written directly to the database | Rejected at generation, previous file kept | Rejected; previous feed file byte-identical | Pass. The rejection came about 26 s into generation, after the product pass (see Observations). |
| Admin form, feed 176: `;`, `&`, `#`, `amp`, `39`, `&#;` with no enclosure | Rejected, database unchanged | Rejected, unchanged (`admin-delim-reject.txt`) | Pass |
| Admin form, `;` with `"` enclosure | Saves, entities intact | `"&lt;b&gt;&quot;Q&quot; &amp; Co; 5 &amp; é&lt;/b&gt;"` (`admin-176-enclosed.txt`) | Pass |
| Generated output with `;`+`"`, `&`+`"`, `#`+`"`, CSV, TSV, quoted TSV, `\|`, `\|\|` | Entities and literal text intact, row count and columns stable | 16/16 for each of the eight formats | Pass |
| Admin form, delimiter `; ` with no enclosure | Rejected, or output keeps entities | Saved ("You saved this feed."). Output: `&lt;b&gt;&quot;Q&quot &amp Co 5 &amp;amp é&lt;/b&gt;` (`admin-176-semi-space.txt`) | **Fail (Defect A)** |
| Generated output, delimiters `; `, `;\|`, `\|&` | As above | 11/15, 13/15, 13/15. Examples: `&quot;Q&quot &amp Co`; `&lt  \|&lt; &quot &quot;`; `&lt;\|  lt; &quot; quot;` | **Fail (Defect A)** |

### Fix 2: Strip tags

| Case | Expected | Actual | Result |
|---|---|---|---|
| Script body, style body, mixed-case script with attributes and spaced close, multiline script containing a closing-tag string | Bodies gone | Gone; no script or style text in any Strip tags cell | Pass |
| Unclosed script | Rest discarded (documented) | Discarded | Pass |
| Entities and encoded tags | Untouched, no decoding | Untouched | Pass |
| `<scripts>`, `<styles>` | Ordinary tags | Ordinary tags | Pass |
| Chains: escape then strip, strip then escape, prepend then strip, strip then append, length limit after strip | As documented | As documented | Pass (5/5) |
| `a < b and c > d, 5<6, x` | Same as default cleaner | `a < b and c > d, 5` | Documented unfinished-tag behavior, not counted as a defect |
| `<p>alpha</p><p>beta</p>gamma<br>delta<li>e</li><li>f</li>` | `alpha beta gamma delta e f` (default cleaner) | `alphabetagammadeltaef` | **Fail (Finding C)** |
| Page Builder `<p>Soft cotton tee.</p><p>Machine wash.</p>` | `Soft cotton tee. Machine wash.` | `Soft cotton tee.Machine wash.` | **Fail (Finding C)** |

### Fix 3: Round

| Case | Expected | Actual | Result |
|---|---|---|---|
| 11 values by 5 precisions, generated feed, PHP `precision` 3, 14, 17, -1 | Plain decimal, no exponent, no `-0`, trailing fractional zeros removed, same text at every setting | 54/55 at each setting, identical text across settings | Pass |
| Same on PHP 8.5.11 | Same | 54/55 | Pass |
| `1234567.123456789` at 12 places | Documented float limit | `1234567.123456788948` | Documented limit |
| Standalone, PHP 8.1.34, 8.2.34, 8.3.35, 8.4.27, 8.5.11 | Same 92 results on every version | 92/92 each, identical digest `c0e7ea0232519a60` | Pass |

### Fix 4: Unknown columns

| Case | Expected | Actual | Result |
|---|---|---|---|
| Admin: ` title`, `title `, `Title`, `titel` | Rejected with the exact name; database unchanged; repair possible | Rejected with the exact name (`" title"`, `"Title"`, `"titel"`); unchanged; corrected save succeeded | Pass |
| Model: ` title`, `title `, `Title`, `titel`, `title` + tab, `TITLE`, and `größe g:size` against a map column `Größe g:size` | Each rejected with its exact name, nothing saved; a modifier on `Größe g:size` itself is accepted and applied | As expected, 15/15 | Pass |
| Admin: add a column and its modifier in one save | Saves; modifier applies | Saved; `promo` uppercased in output | Pass |
| Admin: remove a column but keep its modifier | Rejected | Rejected for `promo2`, unchanged | Pass |
| Rename a column and its modifier together | Saves | Saved | Pass |
| Google feed: modifier on stored legacy `promotions_id` | Rejected, or applied to the runtime `promotion_id` | Accepted; output `PROMO1` unchanged | **Fail (Defect B)** |
| Google feed: modifier on runtime-only `color` (added when `item_group_id` is mapped) | Accepted and applied, or the name documented as unavailable | Rejected as unknown | **Fail (Defect B)** |

### Fix 5: Validation messages (Admin POSTs to feed 176)

| Submitted `filters_column_modifiers` | Expected message | Actual | Result |
|---|---|---|---|
| Scalar string | Must be a list of rows | "Column modifiers must be a list of rows." | Pass |
| Integer-like string | Same | Same | Pass |
| Associative array | Same | Same | Pass |
| Non-sequential list | Same | Same | Pass |
| Rows that are not arrays | Row shape message | "Each column modifier requires a column, operation and text value." | Pass |
| 201 rows | Row limit | "Column modifiers cannot exceed 200 rows." | Pass |
| 200 rows | Saves | "You saved this feed." | Pass |
| Empty string | Clears and saves | "You saved this feed." | Pass |

## Pass, fail and not-run matrix

| Area | Result | Count | Evidence |
|---|---|---|---|
| Escape rejection (save, generation, existing feeds) | Pass | 21/21 | `escape-reject-results.json` |
| Escape, enclosed and safe formats | Pass | 128/128 | `escape-{semi_q,amp_q,hash_q,csv,tsv,tsv_q,pipe,dpipe}-results.json` |
| Escape, mixed delimiters | **Fail (A)** | 37/45 | `escape-{semi_space,semi_pipe,pipe_amp}-results.json` |
| Admin delimiter saves | **Fail (A)** | 7/8 | `admin-delim-reject.txt`, `admin-176-semi-space.txt` |
| Strip tags | Fail (C) | 14/17 | `strip-results.json` |
| Round in generated feeds | Pass | 270/275 | `round-p{3,14,17,-1}-results.json`, `round-php85-results.json` |
| Round standalone | Pass | 460/460 | `round-standalone.txt` |
| Columns and model-level messages | Fail (B) | 33/35 | `columns-results.json` |
| Admin messages | Pass | 8/8 | `admin-post-messages.json` |
| Every operation, value matrix | Pass | 115/118 | `modifier-matrix-results.json` |
| Output formats with modifiers (CSV, TSV, quoted TSV, pipe, multi-character) | Pass | 55/55 | `format-*-results.json` |
| Mapping pipeline, modifiers vs no modifiers | Pass, pre-existing misses | 224/240; option rows 26/26 column-aware | `pipeline-compare-results.json` |
| Preview vs generated | Pre-existing misses | 17/21 for both 183 (modifiers) and 184 (none) | `preview-18{3,4}-results.json` |
| Full vs batched generation, feed 183 | Pass | byte-identical | `full-183.txt`, `batched-183.txt` |
| Store 2, EUR, price modifiers | Pass | 8/8 | `price-currency-results.json` |
| Skip-empty and fill-empty after modifiers | Pass | 5/5 | `misc-skip*-results.json` |
| ISO-8859-1 output | Pre-existing miss | 4/5 | `misc-iso-results.json` |
| Full catalog, cell differential | Pass | 0 mismatches in 2 x 51,595 cells | `fullcat-diff-results.json` |
| Pre-feature output identity | Pass | 23 comparisons: 21 byte-identical, 1 time-derived column, 1 pre-existing row-order variation at HEAD | `baseline-swap.txt`, `baseline-swap2.txt`, `baseline/` |
| Seven presets with modifiers | Pass | 70/70 changes as expected; 1 expected change absent (Secure URL observation); 0 unexpected changes in 625 cells | `presets/check.json`, `presets/*.txt`, `presets/*.log` |
| Due schedule through cron | Pass | 4/4 steps | `cron-s1.txt` to `cron-s4.txt`, `cron-seq.txt` |
| Admin editor, browser | Pass, except keyboard reordering (D) | | `admin-session.har`, `har-summary.json`, `a11y-rows.json` |
| Unit suites | Pass | 1108/1108 twice | `static/phpunit*.txt` |
| New regression tests | Fail as intended | 0/9 | `static/regression-red-*.txt` |
| Static analysis | Pass | see below | `static/` |
| XML output | Not run | | Out of scope: SHO-1 is not merged and XML is not claimed. |
| Upload delivery | Not run | | Prohibited by the brief; uploads disabled. |

## Seven presets with modifiers

Each preset type was created from its shipped default configuration and given modifiers suited to its recipient (`presets.php`). It was generated next to an identical feed without modifiers, then compared cell by cell against independent expectations (`presets-check.php`, `evidence/presets/check.json`). Files and per-feed logs are in `evidence/presets/`.

Fixture for these runs (retest database only): every shipped preset maps `brand` to `manufacturer`, and several require a brand and an MPN or GTIN. Products 1 and 2 received manufacturer `SHO5R Café Co`, and each preset feed maps `mpn` to `sku`. The `manufacturer` attribute was not in this catalog's Default attribute set, so product loads never returned it. It was assigned to the set and the EAV cache was cleaned. In the first pass, before that assignment, Meta, TikTok, Microsoft and OpenAI produced no rows (kept in `evidence/presets-pass1/`); those results are not used.

| Preset (feeds: none / with) | Format | Rows | Modifier effects in output | Modifier columns already in target form | Logs |
|---|---|---|---|---|---|
| Google Shopping (206 / 213) | tab, no enclosure | 5 / 5 | Title append 5/5, Link HTTPS 5/5, Image link HTTPS 4/5 | Price (`100.00 USD`), Condition (`new`), Description (plain text, equal to default cleaner) | Same 3,024 skipped products, 0 errors |
| Meta Catalog (207 / 214) | tab, `"` | 2 / 2 | Title append, Link HTTPS, Brand title case (`SHO5R Café Co` to `Sho5R Café Co`) | Availability (`in stock`), Description | Same 3,031 skipped rows, 0 errors |
| Pinterest (208 / 215) | tab, `"` | 2 / 2 | Title prepend `Pin: `, Image link HTTPS | Price, Description | Same, 0 errors |
| TikTok (209 / 216) | comma, `"` | 2 / 2 | Title append, Link HTTPS | `sku_id` (`1`, `2`), Price, Description | Same, 0 errors |
| Microsoft (210 / 217) | tab, no enclosure | 2 / 2 | Title append, Link HTTPS | Sale price (empty), Description | Same, 0 errors |
| OpenAI (211 / 218) | tab, `"` | 2 / 2 | Title append, Link HTTPS, Additional image link HTTPS | Price, Description | Same, 0 errors |
| Google local inventory (212 / 219) | tab, no enclosure | 32 / 32 | Store code uppercase 32/32 | Price, Quantity | No skips, 0 errors |

Across the seven pairs: identical headers and row sets, 625 cells compared, 70 changed, each exactly as expected. One expected change did not happen: Image link on Google product 10, a malformed `http://127.0.0.1:8152/media/catalog/product/   ` that Secure URL leaves unchanged (see Observations). No cell outside a modifier column changed. Run times with and without modifiers were within 3 s of each other.

Coverage limits: the fixture descriptions are plain text, so Strip tags matched the default cleaner here and Finding C does not show. Price, Integer and case operations on values already in target form prove only that they leave such values alone; their transformations are covered by the value matrix (115/118). Title case follows PHP Unicode title casing, so a letter after a digit is capitalized (`Sho5R`).

## Pre-feature output identity

For this check, HEAD's `Filter.php` and `AdapterAbstract.php` were copied over the installed module, so `modifyRows` no longer existed. Feeds without modifiers were generated, and the snapshot was restored (`swap-baseline.sh`, `swap-baseline2.sh`; `diff -rq` against the snapshot was clean after each). Outputs were compared byte for byte with the same feeds generated by the working tree.

| Pass | Feeds | Result |
|---|---|---|
| 1, original fixture | 170, 174, 184, 195, 198, 200, 203, and presets 206 to 212 | 13/14 byte-identical. Feed 203 (3,035 rows) at HEAD had the same rows, but grouped parent 3041 was on line 32 instead of line 3032. |
| 2, preset fixture (brand, MPN) | Presets 206 to 212 | 6/7 byte-identical. OpenAI (211) differs only in `expiration_date`, which the preset sets to generation time plus one day (`2026-11-12T19:49-0500` vs `19:55`). |
| 2, row order | Feed 203, two more HEAD runs | Both HEAD runs are byte-identical to all five working-tree runs (`e94bc94dc7d28090`). |

Conclusion: no output change without modifiers. The one row move came from HEAD in 1 of its 3 runs. It is the pre-existing unordered product query (pre-existing issue 4), not SHO-5.

## Due schedule through cron

Setup (`cron-feed.php`, `make-app-cron.sh`): `app-cron` is a clone of the retest app whose `env.php` points at `sho5r_cron_20261111`, a copy of the retest database. The guard refuses any other database. Queue rows copied from the source were deleted in the copy only. Feed 220 is a Generic feed for three SKUs with four modifiers: title append ` [cron]`, description Strip tags, link Secure URL, price prepend `#`. Its status is Scheduled, `start_at` 20 (local hour) and `processed_at` on the previous day. It has no upload targets, and the upload table was empty. Every step was one manual, synchronous `bin/magento cron:run --group=mageos_shopping_feed --bootstrap=standaloneProcessStarted=1`, timed by `cron-seq.sh`. No host crontab entry was created. State before and after each step: `evidence/cron-s1.txt` to `cron-s4.txt`.

| Step (local time) | Expected | Actual | Result |
|---|---|---|---|
| s1, 20:06:05 | Schedules generated; feed not yet due | Process jobs for 20:06 to 20:09 created and the 20:06 one ran (`success`); feed 220 status 1, no queue row, no file | Pass |
| s2, 20:10:05 | Schedule job queues the due feed and marks the schedule processed | `mageos_shopping_feed_schedule` at 20:10 `success`; queue row 22 for feed 220; status 2 (queued); `processed_at` 2026-11-11 20:10:06 | Pass |
| s3, 20:11:30 | Process job generates the queued feed | Process job `success` (20:11:30 to 20:11:59); status 4; queue empty; `sho5r_cron_due_220.txt` written (3 rows); log START and FINISHED, 0 errors | Pass |
| s4, 20:12:30 | No second run the same day | Process job `success` with nothing to do; status 4, `processed_at` unchanged, file unchanged, one START in the log | Pass |

Output (`app-cron/pub/media/mageos-shopping-feed/sho5r_cron_due_220.txt`):

```text
id	title	description	price	link
1	Café & "Deluxe" [cron]	Fresh tea & coffee. Unicode: café €; literal closing marker ]]> retained.	#100.00 USD	https://127.0.0.1:8152/cafe-tea-deluxe.html
2	Budget Tea [cron]	Fresh tea & coffee. Unicode: café €; literal closing marker ]]> retained.	#40.00 USD	https://127.0.0.1:8152/budget-tea.html
3	sho3-child [cron]		#100.00 USD	https://127.0.0.1:8152/sho3-child.html
```

All four modifiers applied in the cron path. Uploads: 0 rows before and after; no delivery was attempted. Process jobs at 20:07 and 20:08 were marked `missed` because no `cron:run` happened in those minutes; that is normal Magento behavior for a manual run. `exception.log` in the clone gained no entries.

## Commands, counts and static analysis

All commands ran against the working tree or the isolated copy. Output is under `evidence/static/`.

| Check | Command (abridged) | Result |
|---|---|---|
| PHPUnit 12.5.39, PHP 8.4.27 | `MAGENTO_ROOT=~/code/mageos-latest php8.4 vendor/bin/phpunit -c phpunit.xml.dist` | OK, 1108 tests, 2998 assertions |
| PHPUnit 9.6.34, PHP 8.3 | `MAGENTO_ROOT=~/code/mageosdemo/src php8.3 vendor/bin/phpunit -c phpunit.xml.dist` | OK, 1108 tests, 2611 assertions |
| Regression tests (new) | Same runners, `regression/Sho5RetestRegressionTest.php` | 9 tests, 9 failures, on both runners |
| Node tests | Node 26.12 test runner, module JS tests | 79/79; UI form 8/8 |
| PHP lint | `php -l` on 571 files | 0 failures on PHP 8.1, 8.4 and 8.5 |
| PHPCS | `MageOS_ShoppingFeed` standard over the module | exit 0, no violations (one deprecated-sniff notice) |
| PHPStan 2.3.1, level 8, full | `phpstan analyse -c phpstan.neon.dist` from an isolated export with `vendor/`, result cache cleared | **[OK] No errors**, 2.9 s, 197 MB |
| PHPStan sensitivity probe | Same, with a deliberate `return.type` error added to a throwaway copy of `ModifierChain.php` | Reported at `ModifierChain.php:233`, so the full run was analysing the code |
| PHPStan baseline | `php dev/tests/check-phpstan-baseline.php` | 1714 to 1713 entries, 2403 to 2402 errors; did not grow |
| Composer | `composer validate` | valid |
| Repository validators | `dev/tests/validate.php` (isolated), wiki and Magento XML validators | Pass: 26 XML files, 8 feed types; 46 wiki pages; 52 XML files |

Full static-analysis status: complete and clean. The fixes document reported an internal error on `Magento\Framework\EntityManager\OperationInterface`. I reproduced it only when PHPStan runs from a directory without `vendor/` (`phpstan-repro-novendor-cwd.txt`). The bitexpert Magento extension sets `magentoRoot` to `%currentWorkingDirectory%` and reads the Composer class maps from there. It is a harness condition, not a code defect. Running `validate.php` in the real checkout also fails, because it scans `.claude/worktrees/*` copies (`validate.php.txt`); the isolated copy passes.

### Browser evidence

agent-browser session `sho5r`, 1920x1080, Admin on the retest store. HAR of the whole session: 12,170 requests, all to `127.0.0.1:8152`, 11,994 with status 200. The single 404 was my deliberate GET to the POST-only generate URL. The 175 status-0 entries are requests superseded by navigation (39 form-submit POSTs and repeated static JS loads), not failed responses. Console errors: none. `var/log/exception.log` has one entry, from store setup at 22:32 UTC (catalog rule indexing before `log_bin_trust_function_creators` was set), and none during the browser session (22:58 to 23:37 UTC) or later.

Screens exercised: Columns Map and modifier rows, delimiter and enclosure fields, save and reopen for feeds 176 and 177, Test Feed, and store 2 (EUR). Reopened forms showed the stored columns, modifiers, delimiter and enclosure (`admin-176-reopen-enclosed.json`, `admin-177-reopen.json`).

### Performance

Full catalog, 3,035 rows, two runs each, PHP 8.4, no Xdebug.

| Feed | Modifiers | Run 1 | Run 2 | Peak memory |
|---|---|---|---|---|
| 200 skip-empty | none | 42.0 s | 45.0 s | 90.5 / 86.5 MB |
| 201 skip-empty | 17 marker rows | 42.9 s | 45.4 s | 84.5 / 86.5 MB |
| 202 skip-empty | 200 rows | 46.0 s | 51.7 s | 86.5 / 88.5 MB |
| 203 no skip | none | 49.7 s | 49.6 s | 86.5 / 86.5 MB |
| 204 no skip | 17 marker rows | 45.3 s | 46.5 s | 86.5 / 86.5 MB |
| 205 no skip | 200 rows | 51.5 s | 47.3 s | 86.5 / 88.5 MB |

Run-to-run spread (up to 5.7 s) is as large as any difference between configurations, so 200 modifier rows add no measurable cost at this catalog size. Outputs were byte-identical between runs.

## New defects, ranked

### A. Escape HTML corrupts entities with mixed multi-character delimiters (blocker for Fix 1; severity Medium)

- **Where:** `Model/Product/ModifierChain.php:89-91` accepts any unenclosed delimiter that contains at least one character outside `[a-z0-9&#;]`. The comment on line 89 ("cannot split an entity") is false. `Model/Product/Filter.php:186-192` then replaces every occurrence of the whole delimiter with a space in unenclosed output, including occurrences that span the end of an entity.
- **Why it blocks:** the wiki (`docs/wiki/Columns-and-Directives.md:36`) says save and generation reject unsafe configurations, and the error message tells users to "choose another delimiter". `; ` follows that advice and still corrupts output, silently, in values sent to recipients.
- **Repro (Admin):**
  1. Open a Generic feed. Add the modifier Escape HTML on a column whose source contains `"Q" & Co`.
  2. Set Delimiter to Other, enter `; ` (semicolon space), and leave Cell Enclosure empty.
  3. Save. The form reports "You saved this feed."
  4. Generate. The cell reads `&quot;Q&quot &amp Co`, and an existing `&amp;` becomes `&amp;amp`.
- **Other delimiters:** `;|` turns `&lt;|` into `&lt  |`, and `|&` turns `|&lt;` into `  lt;`.
- **Regression test:** `Sho5RetestRegressionTest::testEscapeWithMixedEntityDelimiterIsRejectedOrKeepsEntitiesIntact`, 3 data sets, all fail. Rejecting the configuration passes the test, as does producing intact entities without the delimiter.

### B. Google runtime column renames are not what save validates (severity Low to Medium)

- **Where:** `Observer/ValidateColumnModifiers.php:39-52` validates against the stored `columns_product_columns`. At generation, `Model/Feed.php:231` (`getColumnsMap`) runs `upgradeGoogleColumns` (`Model/Feed.php:262-272`). That renames `promotions_id` to `promotion_id` and adds variant columns such as `color` when `item_group_id` is mapped. The Admin form shows the raw stored map (`Model/Adminhtml/FeedFormData.php:41`), so users see the legacy name.
- **Effect:** a modifier on `promotions_id` is accepted and then silently never applied, since the column is `promotion_id` when modifiers run. A modifier on `color`, which exists in the output, is rejected as unknown.
- **Repro:**
  1. Open a Google Shopping feed whose stored map has `promotions_id` (any pre-rename feed).
  2. Add Lowercase on `promotions_id` and save. The save succeeds.
  3. Generate. `promotion_id` keeps its original case (`google-legacy.raw`).
- **Regression test:** `testAcceptedGoogleModifierIsAppliedToTheRuntimeColumn` fails with `PROMO1` instead of `promo1`.
- **Not a blocker:** it affects only Google feeds saved before the rename, and the output keeps the unmodified value rather than a corrupted one. It should be fixed before release so the save contract holds.

### C. Explicit Strip tags joins words across block markup (severity Medium, not a contract violation)

- **Where:** `Model/Product/ModifierChain.php:185-189` uses PHP `strip_tags`. The default cleaner, `Model/Product/Filter.php:213-236`, replaces each tag with a space. Columns with an explicit Strip tags row bypass the default cleaner (wiki line 43).
- **Effect:** adding Strip tags to a description makes it worse than having no modifier. `<p>Soft cotton tee.</p><p>Machine wash.</p>` becomes `Soft cotton tee.Machine wash.`, and `gamma<br>delta` becomes `gammadelta`. This matches the documented `strip_tags` behavior, so it is not a contract violation. It does affect the most obvious use of the operation, on Page Builder descriptions.
- **Repro:** add Strip tags on a description column whose source has two paragraphs, then generate.
- **Regression test:** `testExplicitStripTagsKeepsWordBoundariesOfDefaultCleaning`, 5 data sets (paragraphs, line break, list items, Page Builder text, comment), all fail.

### D. Modifier order cannot be changed from the keyboard (severity Low, accessibility)

- **Where:** the `filters_column_modifiers` dynamic rows in the feed form. The only way to change a row's position is the drag handle, a `div.draggable-handle` with `tabIndex` -1 and no name. The `position` input is hidden. Columns Map rows, by contrast, have a visible, labelled Order field.
- **Effect:** chain order decides the result (for example, Escape then Strip tags differs from Strip tags then Escape). A keyboard-only user cannot reorder a chain and must delete and re-add rows in the wanted order. Visible fields are labelled (Column, Modifier, Value).
- **Repro:** open a feed with two modifier rows and Tab through a row. Focus reaches Column, Modifier, Value and the delete button, never the handle.
- **Regression check:** `/private/tmp/sho5r-20261111/a11y-rows.sh` drives the Admin form in the browser session and exits 1 while modifier rows have no keyboard reorder path. Current result: exit 1 (`evidence/a11y-rows.json`). It is a browser script, not a PHPUnit test, because the module has no browser test harness.

### Observations (not defects)

- **Secure URL leaves malformed URLs alone.** `ModifierChain.php:225` requires a URL with no whitespace, so `http://127.0.0.1:8152/media/catalog/product/   ` (fixture product 10, whose image path is blank) stays `http://`. Leaving malformed input alone is defensible, but the wiki row says only that "other schemes and relative paths stay unchanged".
- **Generation-time rejection is late.** A stored unsafe Escape configuration fails about 26 s into generation, after the product pass, although the check depends only on configuration.
- **Price format labels without converting.** With a blank code, Price format appends the feed currency to whatever amount the column holds. On store 2 (EUR), a column mapped to the raw `price` attribute (0.50 in base USD) became `0.50 EUR`, while the converted Price directive gave `0.35 EUR` (`admin-177-store2-eur.txt`). This matches the wiki ("formats the amount; it does not convert currencies"), but the result is a wrong price label if a user maps a base-currency attribute.
- **Date `T` format.** `date(Y-m-d H:i T)` on a fixed-offset input returns `GMT-0500`. That is PHP behavior; my initial expectation of `-05:00` was wrong and is recorded as corrected, not as a defect.

## Pre-existing issues (not caused by SHO-5)

Each was seen during this retest and also occurs without modifiers, at HEAD, or in code SHO-5 does not change. Items 1, 2, 3 and 5 were also listed by the earlier reports.

1. Custom-option rows have 13 cells under a 15-column header, so later cells sit under the wrong headings (`3045-option0`, `3045-option1`). The modifier pipeline applies correctly to the shifted cells (column-aware check 26/26). Feed 184 without modifiers is byte-identical at HEAD.
2. Preview differs from generated output for option rows and for children shared between parents. Same 4 misses with and without modifiers.
3. ISO-8859-1 feeds: entity-decoded text stays UTF-8 (`c3 89` for É in an ISO file without modifiers). With Lowercase the bytes become `e3 89`, which is the same mis-encoding carried through. Output also starts with a UTF-8 byte-order mark.
4. Products are read without `ORDER BY` (`Model/Product/CollectionProvider.php:202`), so MySQL 8 may change row order between runs. Seen once in three HEAD runs of feed 203 (see Pre-feature output identity).
5. Generating a USD feed and then an EUR feed in one PHP process gave the second feed USD values. The EUR price checks were rerun one currency per process (`rerun-price.txt`).
6. Test Feed preview decodes entities for display, so it does not show the escaped output.
7. A failed generation leaves a `.tmp` file.
8. Row delete buttons have no accessible name, in Columns Map rows as well as modifier rows (shared DynamicRows markup).
9. A new schedule row gets `processed_at = CURRENT_TIMESTAMP`, so it is first due the next day.
10. `dev/tests/validate.php` scans `.claude/worktrees/*` copies and fails in a checkout that has them.
11. Full PHPStan must run from a directory with `vendor/` (see Static analysis).

## Limits

- Tested on Mage-OS 4.0.0 with PHP 8.4 (generation also on 8.5 for Round). Not on Adobe Commerce, other Magento versions or MariaDB.
- No upload or delivery to any recipient. Preset validity was judged by the module's own preset validators and logs, not by Google, Meta, Pinterest, TikTok, Microsoft or OpenAI.
- XML output was not tested (SHO-1 is not merged).
- Unit-level evidence for Defect B uses reflection to call the private `upgradeGoogleColumns`; the installed-store reproduction does not.
- Accessibility was reviewed through the accessibility tree and keyboard focus only, not with a screen reader.
- Performance is from one machine with two runs per configuration.

## Resources and cleanup

Nothing was cleaned up, as requested ("Keep for now"). Everything below is local and disposable.

| Resource | State |
|---|---|
| `/private/tmp/sho5r-20261111/` | Harness, `app` (retest store; EAV cache cleaned once after the attribute-set fixture), `app-cron` (cron clone, with `sho5r_cron_due_220.txt`), `wt-copy` (static-analysis export), `novendor-cwd`, `snapshot`, `head`, `evidence/` |
| Database `sho5r_20261111` | Feeds 147 to 219 (`SHO5R ...`), products 3043 to 3045, manufacturer option 11 on products 1 and 2, `manufacturer` assigned to the Default attribute set, `mpn` mapped to `sku` on preset feeds 206 to 211 and 213 to 218, store 2 currency (USD and EUR allowed, EUR default), default scope USD and EUR allowed, a store-2 name on product 3043 |
| Database `sho5r_cron_20261111` | Copy of the retest database with feed 220 (now generated, status 4) and its schedule; queue rows copied from the source were removed in this copy only; 11 `cron_schedule` rows from the manual runs; one grant for the application user |
| MySQL server setting | `log_bin_trust_function_creators=1` (runtime only, needed for the clone) |
| Processes | Built-in PHP server on `127.0.0.1:8152` (still running) |
| Browser | agent-browser session `sho5r`; HAR at `~/.agent-browser/tmp/har/har-1794442645050.har` and a copy at `evidence/admin-session.har` (31.5 MB) |
| Earlier environments | `sho5i_20261111` database and the database container, retained |
| Host cron | None installed. Cron ran only as manual `bin/magento cron:run` in `app-cron`. |
| Repository | Unchanged except this report. Manifest of 842 files all OK at the end. |

## Appendix: regression tests

Path: `/private/tmp/sho5r-20261111/regression/Sho5RetestRegressionTest.php`. Run it by copying it to `Test/Unit/Model/Product/` and running PHPUnit with `--filter Sho5RetestRegressionTest`. It is kept outside the repository so the working tree under test stays unchanged and the module suite stays green. Result on both runners: 9 tests, 9 failures (`regression-red-php84-12.txt`, `regression-red-php83-9.txt`).

```php
<?php

declare(strict_types=1);

namespace MageOS\ShoppingFeed\Test\Unit\Model\Product;

use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use MageOS\ShoppingFeed\Model\Feed;
use MageOS\ShoppingFeed\Model\Generator\Cache;
use MageOS\ShoppingFeed\Model\Product\Filter;
use MageOS\ShoppingFeed\Model\Product\ModifierChain;
use MageOS\ShoppingFeed\Observer\ValidateColumnModifiers;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Regression tests for defects found by the SHO-5 independent retest (2026-11-11).
 * Expected to fail against the tested working tree; see docs/reviews/2026-11-11-sho-5-independent-retest.md.
 */
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class Sho5RetestRegressionTest extends TestCase
{
    /**
     * Defect A: a multi-character delimiter that mixes entity characters with other characters passes
     * save validation, and the delimiter-to-space normalization then cuts entities apart.
     *
     * @dataProvider mixedEntityDelimiters
     */
    #[DataProvider('mixedEntityDelimiters')]
    public function testEscapeWithMixedEntityDelimiterIsRejectedOrKeepsEntitiesIntact(
        string $delimiter,
        string $source
    ): void {
        $feed = $this->feed([
            'output_params_delimiter' => 'other',
            'output_params_delimiter_other' => $delimiter,
            'output_params_enclose_cell' => '',
            ModifierChain::CONFIG_KEY => [['column' => 'title', 'operation' => 'escape', 'value' => '']],
        ]);
        try {
            $this->save($feed);
            $filter = (new Filter(new Cache()))->setFeed($feed);
            $cell = (string)$filter->modifyRows([['title' => $filter->cleanField($source, ['column' => 'title'])]])[0]['title'];
        } catch (LocalizedException) {
            // Rejecting this configuration at save or generation is an acceptable fix.
            $this->addToAssertionCount(1);
            return;
        }
        self::assertStringNotContainsString($delimiter, $cell, 'the delimiter must not leak into an unenclosed cell');
        self::assertDoesNotMatchRegularExpression(
            '/&(?:lt|gt|amp|quot|#0?39)(?!;)|(?<!&)(?<![a-z#])(?:lt|gt|amp|quot);/',
            $cell,
            'broken entity in ' . json_encode($cell)
        );
    }

    /** @return array<string, array{string, string}> */
    public static function mixedEntityDelimiters(): array
    {
        return [
            'semicolon space' => ['; ', 'Tom & Jerry'],
            'semicolon pipe' => [';|', '<b>|x'],
            'pipe ampersand' => ['|&', 'x|<b>'],
        ];
    }

    /**
     * Defect B: Google feeds rename a stored legacy promotions_id column to promotion_id at runtime.
     * Save validates against the stored name, so the modifier is accepted but never applied.
     */
    public function testAcceptedGoogleModifierIsAppliedToTheRuntimeColumn(): void
    {
        $feed = $this->getMockBuilder(Feed::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $feed->setData(['id' => 1, 'type' => 'google_shopping', 'config' => new DataObject([
            'columns_product_columns' => [
                ['column' => 'id', 'attribute' => 'directive_id', 'order' => 1],
                ['column' => 'promotions_id', 'attribute' => 'directive_static_value', 'param' => 'PROMO1', 'order' => 2],
            ],
            ModifierChain::CONFIG_KEY => [['column' => 'promotions_id', 'operation' => 'lowercase', 'value' => '']],
        ])]);
        try {
            $this->save($feed);
        } catch (LocalizedException) {
            // Rejecting a name that will not exist in the output is an acceptable fix.
            $this->addToAssertionCount(1);
            return;
        }
        $runtime = (new \ReflectionMethod($feed, 'upgradeGoogleColumns'))->invoke(
            $feed,
            $feed->getConfig()->getData('columns_product_columns')
        );
        $row = [];
        foreach ($runtime as $mapping) {
            $row[$mapping['column']] = (string)($mapping['param'] ?? '');
        }
        $modified = (new Filter(new Cache()))->setFeed($feed)->modifyRows([$row])[0];
        self::assertSame('promo1', $modified['promotion_id'] ?? null, 'accepted modifier had no effect on the output');
    }

    /**
     * Finding C: explicit Strip tags joins words that the default cleaner keeps apart.
     *
     * @dataProvider blockMarkup
     */
    #[DataProvider('blockMarkup')]
    public function testExplicitStripTagsKeepsWordBoundariesOfDefaultCleaning(string $input): void
    {
        $feed = $this->feed([ModifierChain::CONFIG_KEY => [['column' => 'plain', 'operation' => 'strip_tags', 'value' => '']]]);
        $filter = (new Filter(new Cache()))->setFeed($feed);
        $default = $filter->cleanField($input, ['column' => 'legacy']);
        $row = ['legacy' => $default, 'plain' => $filter->cleanField($input, ['column' => 'plain'])];
        self::assertSame($default, $filter->modifyRows([$row])[0]['plain']);
    }

    /** @return array<string, array{string}> */
    public static function blockMarkup(): array
    {
        return [
            'paragraphs' => ['<p>alpha</p><p>beta</p>'],
            'line break' => ['gamma<br>delta'],
            'list items' => ['<li>e</li><li>f</li>'],
            'page builder text' => ['<div data-content-type="text"><p>Soft cotton tee.</p><p>Machine wash.</p></div>'],
            'comment' => ['a<!-- hidden -->b'],
        ];
    }

    /** @param array<string, mixed> $config */
    private function feed(array $config): Feed
    {
        $feed = $this->getMockBuilder(Feed::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $feed->setData(['id' => 1, 'type' => 'generic', 'config' => new DataObject($config + [
            'columns_product_columns' => [
                ['column' => 'title', 'attribute' => 'name', 'order' => 1],
                ['column' => 'plain', 'attribute' => 'description', 'order' => 2],
                ['column' => 'legacy', 'attribute' => 'description', 'order' => 3],
            ],
        ])]);
        return $feed;
    }

    private function save(Feed $feed): void
    {
        (new ValidateColumnModifiers(new ModifierChain()))->execute(new Observer([
            'event' => new Event(['object' => $feed]),
        ]));
    }
}
```
