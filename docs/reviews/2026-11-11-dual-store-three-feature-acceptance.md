# Three-feature demo deployment and functional acceptance

Tested November 11, 2026. Both requested stores have the combined candidate installed. Server-side functional tests, the scoped authenticated Admin acceptance suite, and storefront smoke checks pass. Matt completed two-factor setup before the Admin continuation. Two Admin issues were repaired and retested on both stores: stale published assets and missing preview diagnostics. The exclusions below remain explicit.

## Candidate and deployment

- SHO-1 XML output and fixes: `e368635`, from `/private/tmp/shopping-feed-sho-1`.
- SHO-2 conditional values and SHO-3 product selection: `223add5`, from the main working directory.
- No existing worktree contained all three features. An isolated combined checkout was prepared at `/private/tmp/shopping-feed-deploy-20261111/candidate`.
- Base combined Git tree: `2e8e02c9bd501a41dd0eada7fa75612df8757863`. This is a tree identifier, not a new commit. Documentation, translation, and one test-file conflict were resolved; runtime files merged automatically. The final candidate additionally contains the preview-diagnostics fix linked below. The exact combined implementation was retained locally as integration commit `3deb34d`; it is not a new PR. The preview fix belongs to existing PR #27 (`feature/sho-2-sho-3`), where the logger reset was introduced.
- Both stores use Mage-OS 4.0.0 / Magento 2.4.9 in production mode. Installation replaced 53 paths in `vendor/mage-os/module-shopping-feed` per store. Composer's installed-package metadata still describes the bundled release; this source overlay can be overwritten by a future Composer reinstall/update.
- Deployment was sequential. Cron was stopped during each build; maintenance covered copying, DI compilation, and Admin static deployment. Schema was already current, so no database upgrade was run.
- Both `setup:di:compile` and Admin `setup:static-content:deploy` passed. The final checksum-based `rsync` comparison found no deployed-source differences. Both stores have maintenance disabled, cron running, and all indexers Ready.
- No pushes, merges, releases, provider submissions, or catalog-product edits were made. Matt authorized local commits, then clarified that fixes should stay on their existing PR branches.

## Verification

| Layer | Result |
| --- | --- |
| Combined PHP unit suite on local Mage-OS 4 framework | 1,066 tests, 3,048 assertions passed after the preview fix |
| Frontend, CI, actual-framework form, and selection-editor JavaScript | 95 tests passed |
| PHPStan | Original full analysis passed; changed Generator.php also passed focused analysis after the fix |
| PHPCS | No source findings; installed-tool deprecated-sniff notices remain |
| Module, wiki, and Magento XML validators | Passed; 26 XML files and 47 wiki pages/sidebar targets |
| Server functional assertions | 125 positive assertions per store across three scripts; see harness corrections below |
| Original data preservation | Alpha: 1,951 original rows and one output file unchanged. Hyvä: 297 original rows and two output files unchanged. Both application configuration files unchanged |
| Public feed download | Sample XML from each public hostname has the same SHA256 as its inspected generated file |
| Authenticated Admin acceptance | Both stores: create, validation, editor transitions, save/reopen, previews, clone/delete, Run Now, and XML links passed |
| Final operational state | Zero schedules, upload destinations, or queue rows; all 17 QA feeds per store disabled |

The PHP and JavaScript suites used the exact combined source with the local Mage-OS framework. They were not rerun inside the remote production containers. The installed functional suites exercised the remote source, database, generator, queue, and files.

Functional coverage on each installed store:

- All eight feed types produced positive output. The six destination XML presets parsed successfully and matched their delimited twins' exported-row counts. Generic custom XML and OpenAI delimited output also passed. Row-count parity is not a field-by-field provider certification.
- The representative 40-object sample included 30 simple products and two each of configurable, bundle, grouped, virtual, and downloadable products. Composite and inventory expansion produced more rows than input objects.
- Saved selection rules exported exactly two selected simple products while conditional true/fallback values appeared in their XML. Zero values, attribute output, percentage adjustment, multiline wrappers, repeated output stability, and preview isolation passed.
- Invalid selection attributes and invalid XML names were rejected before persistence. Empty selection rules restored eligible products; deliberately unmatched selection produced complete empty XML.
- All/Any, SKU wildcard, image presence, parent scope, and child selection passed. A rejected configurable parent did not suppress its selected child.
- The real `Cron\Process` handled six two-object batches over 12 eligible virtual products. XML was published only after completion, matched the unbatched reference byte-for-byte, contained unique identifiers and one header/footer, and left no queue or temporary file.
- Cloning created a separate disabled feed and retained selection data. Structured selection JSON retained its values.

### Harness corrections

The retained raw logs include harness failures; they are not silently relabeled as successful runs:

1. The initial probe used Default store view, which has no product website assignments. The catalog is assigned to Wands: store 2 on Alpha and store 3 on Hyvä. The sample probe was changed accordingly.
2. The harness initially supplied an array and `in` for a list condition. The supported stored form uses a comma-separated scalar and `()`; correcting the fixture allowed the suite to proceed.
3. Two assertions in the main 59-check run were incorrect. Conditional output is subject to existing HTML cleanup, so `match & <special>` correctly becomes `match &` before XML escaping. Also, `getConfig()` returns its fallback for the empty list, while raw persisted configuration is `[]`. The extended suite verified both corrected expectations against actual retained output and raw persistence. The main run therefore has 57 passes and two superseded harness assertions; the extended suite has 28 passes and the operations suite 40 passes. There is no claim that the original 59-check log has zero failures.

The initial server-only run did not establish a product-code defect. The later Admin continuation found the two issues documented below. Additional read-only probe corrections used the module's custom generator factory and the DataObject-backed configuration; those helper errors were not application failures.

## Catalog size and generation estimate

Both databases contain 107,815 products. With all six product types, out-of-stock inclusion, and the populated Wands website, the initial collection contains 79,734 eligible top-level objects. Parent expansion and provider validation change final row counts.

| Sample | Alpha | Hyvä | Linear estimate over 79,734 objects |
| --- | ---: | ---: | --- |
| Generic, 100 simple objects | 1.040 s | 1.129 s | 13.8–15.0 minutes |
| Google Shopping, 100 simple objects | 2.725 s | 2.845 s | 36.2–37.8 minutes |
| Rich presets, 40 mixed objects | Approximately 1.6–1.8 s | Approximately 1.6–1.8 s | Approximately 53–60 minutes |

These are small-sample estimates, not measured full-catalog capacity. Shipping, cache temperature, product expansion, parent predicates, concurrent load, and the chosen mappings can materially change runtime. Allow roughly 15–60 minutes per full feed as an initial planning range, then measure the intended feed before scheduling it.

Normal functional runs were limited to 40 input objects; targeted checks used 1–30, and benchmarks used 100. The harness adds an ID predicate to the generator's collection and adjusts its total count. This is explicitly a test bound, not a deployed product feature. Product-selection rules themselves run after loading and do not reduce the initial scan. The cron test used the ordinary saved virtual-product type filter, with no harness collection bound.

## Authenticated Admin continuation

- Both storefront home pages load. Alpha search returned the expected products. Alpha's Monitor Riser Desk and Hyvä's configurable Saxonburg duvet product opened, added to cart, and were removed again. Both test carts were left empty.
- Hyvä's exercised browser flow reported no page errors. Alpha recorded one blank page-error entry during cart navigation; no accompanying console detail was available, and the flow completed. This is not a zero-browser-error claim for Alpha.
- Both existing Admin sessions were authenticated after Matt completed 2FA. No authentication bypass was used.
- On both stores, changing an existing SKU column to Conditional Value worked on the first transition and after switching to Static Value and back. Saved conditional conditions and both branches survived save/reopen.
- Fixed text, product attribute, and percentage output modes passed browser previews. Both true and fallback branches were exercised. Hyvä returned 110.00 for +10% of a raw price of 100; Alpha returned 90.00 for -10%. Both stores also returned their selected SKU attribute.
- Product selection All/Any, SKU wildcard, parent scope, image-presence controls, adding/removing a condition, and save/reopen were exercised. A deliberately nonmatching wildcard suppressed the preview row and displayed its selection-rejection diagnostic. Using `is` with `*` initially produced no match, correctly; the fixture was corrected to the explicit `matches wildcard` operator.
- Preview lookup passed by SKU and product ID. A nonexistent SKU produced a useful validation error, and valid product-ID input recovered successfully.
- XML/Delimited toggles displayed the appropriate controls. Multiline XML headers persisted. Invalid XML item names were rejected on both stores and corrected successfully.
- The creation menu exposed all eight presets. Fresh Generic creation rejected a blank required name, then saved successfully. Each fresh test feed was immediately deleted, including its default schedule. Cloning the virtual reference retained XML, conditional mapping, and product selection, created a disabled copy, and deletion succeeded. Neither temporary Admin clone nor fresh-creation fixture remains.
- Admin Run Now confirmations queued only Alpha feed 13 and Hyvä feed 14. Their persisted native product-type filter is virtual-only. Each run completed with 11 unique output products, 11 rows and zero skipped rows. Each XML document parsed and contained `admin accepted & tested`, correctly escaped in the raw XML. Both public XML links were opened from the grid and displayed all 11 rows in the browser. The View Log action showed each completed run. The feeds were then disabled again.
- Grid data is a snapshot; reopening the grid refreshes the completion state. Intermittent browser-control timeouts and transient extension-UI warnings were recovered by inspecting the current page. They are not counted as application failures. Native field edits were verified after saving, rather than assuming automation input persisted.
- Provider ingestion, external FTP/SFTP delivery, clock-triggered scheduling, exhaustive ACL roles, completed orders/payments, and a full rollback rehearsal were not performed. Full-catalog runs were excluded from this bounded phase and are recorded separately in the [authorized full-generation follow-up](2026-11-11-full-catalog-generation.md). These are not implied by the server-side checks.

## Admin issues fixed and retested

1. **Stale published Admin assets hid the conditional editor.** Vendor source contained the new conditional module, but published `parameter.js` and `parameter.html` were older. The initial static deployment had not replaced existing files. The repair backed up and quarantined the module directories for Magento/backend and MageOS/m137-admin-theme, plus the module's preprocessed Admin files, then rebuilt both themes. Six conditional/selection JS and template files now match vendor source in each theme. Saved editors and cold/cached directive transitions pass on both stores. Future deployments of this overlay must clear these module assets and verify published-file parity; vendor-only checks are insufficient.
2. **Preview generation discarded its in-memory diagnostic handler.** `Generator::run()` reset the shared logger, removing the handler installed by the preview block. The focused fix preserves only MemoryStream handlers in preview mode and still discards previous feed-file handlers. It changes one deployed PHP file, with per-store backup and inverse script, followed by a graceful PHP reload. The [reviewable zero-context patch for PR #27](2026-11-11-admin-evidence/preview-diagnostics-fix.patch) includes the regression test and test-mock adjustment. The regression was observed red without the fix, green with it, and repeated by reverting/restoring the fix. Clover confirms the actual `run()` method and changed lines executed. The full PHP suite passed 1,066 tests / 3,048 assertions; focused PHPStan and PHPCS passed. Live previews now display normal diagnostics and the reason a selection excluded a product on both stores.

Browser evidence: [Alpha preview](2026-11-11-admin-evidence/alpha-preview-passed.png), [Hyvä preview](2026-11-11-admin-evidence/hyva-preview-passed.png), [Alpha XML](2026-11-11-admin-evidence/alpha-xml-download.png), [Hyvä XML](2026-11-11-admin-evidence/hyva-xml-download.png), [check record](2026-11-11-admin-evidence/admin-evidence.json). Final preservation: [Alpha](2026-11-11-admin-evidence/alpha-preservation-after-admin.json), [Hyvä](2026-11-11-admin-evidence/hyva-preservation-after-admin.json).

Evidence: [regression red](2026-11-11-admin-evidence/preview-regression-red.log), [regression green](2026-11-11-admin-evidence/preview-regression-green.log), [executed coverage lines](2026-11-11-admin-evidence/preview-coverage.txt). The local combined integration commit is retained for reproducibility. The focused fix and this combined-test report are committed on the existing SHO-2/SHO-3 branch for PR #27; no push has been made. PR #26 does not contain the logger-reset regression and receives no duplicate code change.

## Retained state, evidence, and rollback

- Admin repair backups: each store's `admin-assets-repair/` and `preview-diagnostics-fix/` directories under the deployment backup. The PHP fix has its own `rollback.sh`; the asset repair retains the original archive and quarantined directories.
- Remote backup and scripts: `/opt/comtom/backups/shopping-feed-20261111/`.
- Code rollback: `bash /opt/comtom/backups/shopping-feed-20261111/rollback.sh alpha` or `... hyva`. It restores only the backed-up module, rebuilds DI/Admin assets, cleans relevant caches, reloads PHP, and restores service. No database restore is needed for this code-only deployment. Disable/remove test configurations using new directives before rollback; older code cannot interpret them. The rollback script is prepared, not rehearsed.
- Each store has module, feed-table, and original-output backups. The feed-table backup is for scoped recovery, not an instruction to overwrite newer shared data.
- Local logs, outputs, scripts, timing data, and before/after Admin screenshots: `/private/tmp/shopping-feed-deploy-20261111/`.
- Remote functional evidence: `/opt/comtom/stores/{alpha,hyva}/src/var/shopping-feed-test-20261111/` and the corresponding backup subdirectories.
- Seventeen QA feeds per store remain disabled after Admin acceptance. They have no schedules or uploads. Their generated files use the unique `qa_20261111_` prefix. Original feeds were not generated or edited.
- Source worktrees and unrelated dirty files were preserved. This report and its evidence directory are the task additions in the original working directory; the focused implementation fix is retained on the existing SHO-2/SHO-3 branch as well as the local combined integration artifact.

Primary commands included `phpunit -c phpunit.xml.dist`, `node --test`, `phpstan analyse --debug --memory-limit=2G`, `phpcs`, module/wiki/XML validators, production DI/static deployment, the installed `functional.php`, `extended.php`, and `operations.php` harnesses, `setup:db:status`, `indexer:status`, checksum-based source comparison, and row/file comparison against the pre-deployment backups.

## Existing PR branch verification

The preview handler regression was reproduced without the fix on PR #27's standalone `feature/sho-2-sho-3` source, then passed with the fix restored. Its complete standalone suite passed **1,020 tests / 2,864 assertions**. This is separate from the combined XML+conditions suite above. Clover recorded execution of `Generator::run()` and the changed handler-preservation statements. The regression continues to assert that an earlier feed-file handler is discarded. Installation/troubleshooting guidance now covers stale published Admin assets.

Standalone PR #27 evidence: [PHPUnit](2026-11-11-admin-evidence/pr27-phpunit.log), [red regression](2026-11-11-admin-evidence/pr27-regression-red.log), [green regression](2026-11-11-admin-evidence/pr27-regression-green.log), [coverage](2026-11-11-admin-evidence/pr27-coverage.txt), [focused PHPStan](2026-11-11-admin-evidence/pr27-phpstan.log), and [PHPCS](2026-11-11-admin-evidence/pr27-phpcs.log). PHPStan exposed the test reader's `string|false` return type; the regression now asserts a string before checking its content.
