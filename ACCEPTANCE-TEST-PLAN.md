# Production Acceptance Test Plan

## Purpose

Use this plan to decide whether a `MageOS_ShoppingFeed` release candidate is ready for production. Run it against the exact candidate commit on representative store copies before enabling live schedules or uploads.

The [1.2.5 release notes](docs/releases/1.2.5.md) and [product-attribute verification record](docs/reviews/2026-10-22-pr-24-verification.md) identify the current patch scope. The [1.2.4 release notes](docs/releases/1.2.4.md) and [Mage-OS 3.6 security verification](docs/reviews/2026-10-20-standards-security-mageos36.md) cover the previous release. The earlier [Mage-OS 3.5 hardening record](docs/reviews/2026-10-08-hardening-and-mageos-3.5-verification.md) and [native theme follow-up](docs/reviews/2026-10-08-remote-ci-and-native-theme-acceptance.md) retain their recorded scope. The [October 3 local acceptance record](docs/reviews/2026-10-03-local-acceptance.md) retains its `133af71` browser/operational scope. External recipient, target-store, and release approval remain separate gates. The checkboxes below are a reusable plan, not a completed acceptance record.

The current platform acceptance target is Mage-OS 3.6.0; Mage-OS 3.5.0 remains in CI coverage. Add another currently supported Magento Open Source store when available. A store with Multi-Source Inventory is required for Local Inventory acceptance.

## Release record

Complete this table once per candidate.

| Item | Value |
| --- | --- |
| Candidate commit | |
| Package version | |
| Tester | |
| Test start and end | |
| Store URL | |
| Platform and version | |
| PHP version | |
| Database version | |
| Search service and version | |
| Store timezone | |
| Base and display currencies | |
| Catalog size | |
| MSI enabled | Yes / No |
| Existing feed modules | |
| Google test destination | |
| FTP or SFTP test destination | |

Keep command output, screenshots, generated files, checksums, remote delivery evidence, and Merchant Center diagnostics with this record.

## Required store coverage

| Profile | Required purpose | Assigned store |
| --- | --- | --- |
| A | Mage-OS 3.6.0, PHP 8.4, clean module install | |
| B | Currently supported Magento Open Source release | |
| C | MSI store with at least two enabled sources linked to one stock | |
| D | Store with an original Rocket Web feed package installed, if coexistence is part of release acceptance | |

Profiles may share a store when it satisfies every stated condition. Profile A and Profile C are mandatory.

## Safety controls

- [ ] Test on staging or an isolated production copy with a current database and media backup.
- [ ] Record the restore command and responsible operator before installation.
- [ ] Keep module cron disabled until the schedule section of this plan.
- [ ] Keep uploads disabled until generated files have been reviewed.
- [ ] Use a Merchant Center test data source, test account, or non-serving destination.
- [ ] Use remote filenames that cannot overwrite an active production feed.
- [ ] Do not reuse a live promotion feed or live Local Inventory destination.
- [ ] Redact passwords, private keys, access tokens, customer data, and internal paths from shared evidence.
- [ ] If an original Rocket Web module is present, do not disable, uninstall, migrate, or alter it during coexistence testing.

## Test data

Prepare known SKUs and record their expected values before generation.

| Fixture | Required state | SKU or ID |
| --- | --- | --- |
| Simple A | Enabled, visible, in stock, regular price | |
| Simple B | Enabled, visible, sale price with active dates | |
| Simple C | Out of stock | |
| Simple D | Backorders enabled with zero quantity | |
| Simple E | Stock management disabled | |
| Simple F | Disabled or excluded from catalog | |
| Configurable A | Two enabled children with different price and stock states | |
| Configurable B | Children split across at least two MSI sources | |
| Grouped A | Two associated products | |
| Bundle A | Required and optional selections | |
| Downloadable A | Enabled and visible | |
| Text edge case | Quotes, commas, tabs, line breaks, HTML, and non-ASCII text | |
| Category edge case | Included, excluded, disabled, and mapped categories | |
| Promotion A | Active cart rule that should be exported | |
| Promotion B | Inactive or expired cart rule that should not be exported | |

For MSI, record the expected source code, Google store code, physical quantity, reservation-adjusted salable quantity, and availability for every Local Inventory SKU.

## 1. Candidate and automated checks

- [ ] Confirm the checkout is clean and points at the recorded candidate commit.
- [ ] Confirm `composer.json` declares `OSL-3.0`.
- [ ] Confirm the repository contains no conflicting license identifier.
- [ ] Run `composer validate --strict --no-check-publish`.
- [ ] Run `php dev/tests/validate.php`.
- [ ] Run `php dev/tests/validate-wiki.php` and verify the release page, navigation, links, and documentation baselines.
- [ ] Run `node --test dev/tests/frontend/*.test.cjs dev/tests/ci/*.test.cjs` and the actual-framework form checks for the intended runtime.
- [ ] Run PHP syntax checks for every PHP and PHTML file.
- [ ] Run `php dev/tests/validate-magento-xml.php <magento-root>`.
- [ ] Run the unit suite with the candidate checkout and the target platform's PHPUnit installation.
- [ ] Confirm the PHPUnit 12 run reports no PHPUnit notices.
- [ ] Run Magento coding-standard checks used by CI.
- [ ] Run `vendor/bin/phpcs` without errors or warnings and `vendor/bin/phpstan analyse --memory-limit=-1` at level 8. Compare the baseline with the PR base using `php dev/tests/check-phpstan-baseline.php --git-ref=<base-sha> phpstan-baseline.neon`; do not absorb new findings into it.
- [ ] Audit the locked platform, extension, and development-tool dependencies using the policies in `.github/workflows/check-extension.yaml`. Keep the known legacy Flysystem finding visible only in its documented compatibility profiles; additional or hidden advisories fail acceptance.
- [ ] Run the module integration suite used by CI.
- [ ] Run `bin/magento setup:di:compile` in production mode.
- [ ] Confirm every required GitHub check is green for the exact candidate commit.

Acceptance: every command exits successfully. Warnings require an attached explanation and explicit release approval.

## 2. Installation and upgrade behavior

Run on Profile A first.

- [ ] Capture `bin/magento module:status MageOS_ShoppingFeed` before installation.
- [ ] Install the candidate through the same Composer or source path intended for release testing.
- [ ] Run `bin/magento module:enable MageOS_ShoppingFeed`.
- [ ] Run `bin/magento setup:upgrade`.
- [ ] When upgrading to 1.2.4, confirm the nullable SFTP fingerprint column is added and existing destinations remain present. Missing fingerprints must prevent SFTP authentication until trusted keys are configured.
- [ ] When upgrading to 1.2.5, confirm `setup:upgrade` creates the **Skip from Being Submitted** attribute (`mageos_shopping_feed_skip_submit`, Yes/No, default No, store-view scope) in the **Shopping Feed** group of every product attribute set, or leaves a merchant-created attribute with that code unchanged. Confirm a second `setup:upgrade` changes nothing.
- [ ] Run `bin/magento cache:clean`.
- [ ] In production mode, deploy static content for the store locales.
- [ ] Confirm `bin/magento module:status MageOS_ShoppingFeed` reports enabled.
- [ ] Confirm `setup:upgrade` is idempotent on a second run.
- [ ] Confirm the `mageos_shopping_feed_*` tables exist and contain no unexpected data.
- [ ] Confirm the output directory is `pub/media/mageos-shopping-feed`.
- [ ] Confirm module logs are limited to `var/log/mageos_shopping_feed_*.log`.
- [ ] Confirm the Admin and storefront load without PHP exceptions, JavaScript errors, or broken static assets.
- [ ] Confirm indexers remain valid or complete normally after reindexing.

Acceptance: installation is repeatable, compilation succeeds, and no unrelated tables, configuration, files, or modules are changed.

## 3. Identity and coexistence

Run Profile D when an original Rocket Web module is available.

- [ ] Record the original module's enabled state, table names, configuration, output files, logs, cron jobs, and CLI commands.
- [ ] Install and enable `MageOS_ShoppingFeed` without changing the original module.
- [ ] Confirm both Admin areas remain available under their own routes and ACL resources.
- [ ] Confirm the new module uses only `mageos_shopping_feed_*` tables and configuration paths.
- [ ] Confirm the original feed records, schedules, upload records, files, and logs are unchanged.
- [ ] Generate one new-module feed with a unique filename and no upload destination.
- [ ] Confirm the original module's file and status do not change.
- [ ] Confirm CLI commands, cron group, cache identity, RequireJS aliases, layout handles, and UI component names do not collide.

Acceptance: the modules can be evaluated together without migration, overwrite, duplicate remote submission, or mutation of the original installation.

## 4. Admin access and feed lifecycle

- [ ] Sign in as a full administrator and open **Catalog > Mage-OS Shopping Feed > Feeds Management**.
- [ ] Open global settings under **Stores > Configuration > Mage-OS > Mage-OS Shopping Feed**.
- [ ] Create each bundled preset. For this candidate: Generic, Google Shopping, Google Local Inventory, Meta, Microsoft, TikTok, Pinterest, and OpenAI Google-compatible beta. Google Promotions is configured inside Google Shopping.
- [ ] Inspect and remove default schedules/uploads before saving disposable fixtures on a shared store. Confirm the microdata role has not changed unexpectedly, including for types without a visible control.
- [ ] Save each feed, reload it, save again without changes, and confirm every configured field persists. Include Save and Continue Edit.
- [ ] Edit columns, filters, categories, product-type modes, schedules, and upload rows. Confirm add, edit, and delete controls work.
- [ ] Remove the final schedule, upload, and find/replace row; save and confirm the stored collections are empty.
- [ ] Change Store View, save and reload, and confirm category and attribute choices reflect that store before editing them.
- [ ] Clone a feed. Confirm a new disabled identity and copied child records; inspect filenames and destinations before generation. Test `%s` substitution and a literal filename separately: current literal filenames are copied unchanged and can collide with the source.
- [ ] Test grid filters, sorting, mass actions, generation action, log view, and delete action.
- [ ] Test Cancel/Confirm on individual Delete Feed. Test bulk Delete only on an exact disposable selection: the standard grid currently deletes immediately without a confirmation dialog. Verify original feeds remain untouched.
- [ ] Submit malformed required fields and confirm validation is clear and the stored feed is unchanged.
- [ ] Try an output path outside `pub/media/mageos-shopping-feed`. Confirm it is rejected or safely contained.
- [ ] Create a restricted Admin role with view access only. Confirm mutation routes and controls are unavailable.
- [ ] Test save-only, generate-only, and delete-only roles separately. Verify the rendered row/bulk menus, new-feed button, permitted page access, and HTTP 403 for forbidden routes; confirm an empty Actions menu is omitted.
- [ ] Test Feed requires `MageOS_ShoppingFeed::generate` in 1.2.4. Confirm denial for grid-only/save-only roles and hidden current/legacy editor preview buttons. The standard and Nebula grids can still show the link to a read-only user; record that visibility separately from controller authorization.
- [ ] Use a role without module access. Confirm direct Admin URLs are denied.
- [ ] Confirm state-changing requests enforce the Admin form key.
- [ ] Verify custom data-provider modifiers and parameter editors, including unknown stored values and failure recovery. Preserve passwords in storage while excluding decrypted or newly typed values from recovered browser data.
- [ ] Preserve an unexposed custom `config` key through save, reopen, and failed-save recovery; verify a modifier-owned key round-trips without allowing child reassignment or secret disclosure.
- [ ] Repeat new-form acceptance on Magento Open Source 2.4.8, 2.4.9, and the intended Mage-OS version. Record standard Admin, Nebula fallback, and native bridge rendering as separate profiles; no native bridge acceptance exists for this candidate.

### Category save-to-generation regression

- [ ] Begin with a category that has no saved mapping; set priority zero, taxonomy, and product type, then save and reopen.
- [ ] Confirm the saved category object carries its embedded ID as well as priority and mapped values. Do not rely on the numeric map key surviving generator sorting.
- [ ] Preview a product assigned to that category and generate the full disposable feed; verify taxonomy and product-type output without warnings or exceptions.
- [ ] Repeat with inherited values, exclusion, inactive categories, store-root changes, and existing mappings. Preserve unknown saved category entries.
- [ ] On disposable fixtures, send malformed category containers, scalar rows, and rows missing required fields through both the JSON envelope and legacy JSON-string request form. Require a recoverable validation message and unchanged stored data, without a warning or uncaught `TypeError`. The original candidate failed this check; local repair coverage now includes controlled Admin requests. A normal UI round trip does not exercise it.

Acceptance: authorized workflows work, unauthorized users cannot read or mutate feed configuration, and unsafe output paths cannot escape the module directory.

## 5. Single-product test generation

For every fixture SKU that applies, run:

```bash
bin/magento mage-os:shopping-feed:generate <feed_id> <sku>
```

- [ ] Confirm the command exits successfully and identifies the requested product only.
- [ ] Confirm invalid feed IDs and missing SKUs fail clearly without creating a normal feed or queue entry.
- [ ] Compare every displayed value to the Admin product, website, currency, tax, category, and inventory state.
- [ ] Confirm the test command does not alter the last successful production file.
- [ ] Confirm secrets and credentials do not appear in console output or logs.
- [ ] Exercise the Admin Test Feed form separately with valid string SKUs, numeric SKUs, and positive product IDs. Send array-valued/malformed lookup inputs and unsupported modes through controlled requests; require a clear recoverable message. The repaired controller separates feed identity from lookup mode and rejects malformed inputs before product lookup.

## 6. Generic feed output

- [ ] Generate the full Generic feed from Admin and CLI.
- [ ] Confirm the file is created only in the configured safe output directory.
- [ ] Confirm headers, delimiter, enclosure, encoding, and line endings match configuration.
- [ ] Confirm columns remain in the configured order and duplicate column names behave as configured.
- [ ] Confirm quotes, delimiters, line breaks, HTML, Unicode, and empty values do not corrupt rows.
- [ ] Submit column names containing tabs, newlines, and carriage returns through the save boundary. Require field-specific rejection without coercing literal values or structured directive parameters. The local repair adds model save validation. Verify configured delimiters and enclosures separately: before repair, an isolated probe corrupted unenclosed TSV headers but preserved a newline-containing header as one logical quoted CSV record.
- [ ] Confirm included and excluded categories behave as configured.
- [ ] Confirm disabled products and products excluded by filters are absent.
- [ ] Set **Skip from Being Submitted** to Yes for one simple product and one configurable child, first in All Store Views and then only in a non-default store view. Confirm each is absent only from feeds for the store views where the value is Yes, that the feed log names the skipped product, and that No restores it.
- [ ] Map a dropdown and a multiselect attribute that have store-view option labels. Confirm a non-default store view feed carries that store's labels, falls back to the admin label where no override exists, and keeps multiselect order.
- [ ] Confirm configured find-and-replace, price buckets, output limits, and empty-column rules work.
- [ ] Confirm simple, configurable, grouped, bundle, and downloadable product modes match their parent and associated-product settings.
- [ ] Enable complex-product duplicate checking and confirm a child is not emitted both standalone and as an associated product.
- [ ] Confirm URLs use the selected store view and are valid externally.
- [ ] Confirm image URLs, additional images, stock, quantity, weight, identifiers, and custom attributes match the source catalog.
- [ ] Confirm regular, final, special, tier, tax-inclusive, and tax-exclusive prices match the selected feed settings.
- [ ] Confirm currency conversion uses the selected store and feed currency.
- [ ] Edit an existing feed with a blank saved currency when its effective generation currency differs from the store default. Confirm the editor displays the effective currency and an unchanged save preserves prices and currency. Check new-feed defaults and explicit saved selections separately.
- [ ] Count expected products and compare that count with file rows, generator status, and logs.

Acceptance: the file parses without row-shape errors and every sampled value matches the recorded source expectation.

## 7. Google Shopping feed

- [ ] Generate the Google Shopping feed with its default columns.
- [ ] Validate `id`, `title`, `description`, `link`, `image_link`, `availability`, `price`, `condition`, `brand`, `gtin`, `mpn`, `google_product_category`, and `product_type` for representative SKUs.
- [ ] Confirm configurable children use stable IDs, correct item group IDs, correct deep links, and the configured inheritance rules.
- [ ] Confirm every row with `item_group_id` also has `item_group_title`, `variant_option`, and each applicable standard variant attribute.
- [ ] For apparel variants, confirm `color`, `size`, `gender`, and `age_group` match the selected child product.
- [ ] Confirm sale price and sale price effective dates match Magento dates in the store timezone.
- [ ] Confirm equal or greater sale prices produce empty `sale_price` and `sale_price_effective_date` values.
- [ ] Confirm the header contains `sale_price` and `sale_price_effective_date` and has no empty column names.
- [ ] Confirm taxonomy autocomplete and saved category mappings work after reload.
- [ ] Confirm shipping values and weight units match Merchant Center expectations.
- [ ] Confirm the product header is `promotion_id`, never `promotions_id`, and IDs appear only for products and rules that qualify.
- [ ] Parse the full file with the intended delimiter and encoding. Confirm each row has the expected column count.
- [ ] Confirm no product line ends with a tab delimiter.
- [ ] Upload to a non-serving Merchant Center data source.
- [ ] Record item count, parse results, warnings, and disapprovals after processing completes.
- [ ] Investigate every account-level or item-level error. Classify warnings as accepted, store-data issues, or release blockers.

Acceptance: Merchant Center accepts the file format, sampled products match Magento, and no module defect causes an item disapproval.

## 8. Local Inventory and MSI

Run on Profile C with two sources and at least one reservation against a test SKU.

- [ ] Confirm Magento Inventory APIs are enabled and both sources are linked to the tested website stock.
- [ ] Configure an explicit Magento source code to Google store code mapping.
- [ ] Generate a Local Inventory feed in parent-only mode.
- [ ] Generate it in associated-only mode.
- [ ] Generate it in parent-and-associated mode.
- [ ] Confirm each eligible SKU has one row per intended source and no duplicate row for the same SKU and store code.
- [ ] Confirm sources not linked to the website stock are absent.
- [ ] Confirm each row uses the correct mapped store code.
- [ ] Confirm source quantity and availability are calculated for the same source.
- [ ] For configurable parents, confirm quantity and availability use their associated products while associated rows remain controlled by the selected mode.
- [ ] Confirm a second source row is not removed by normal duplicate-product protection.
- [ ] Confirm parent and child adapters do not remain in test mode after generation.
- [ ] Confirm in-stock, out-of-stock, backorder, and unmanaged-stock fixtures produce the expected availability.
- [ ] Place a test order or reservation, regenerate, and confirm reservation-aware salable quantity changes as expected.
- [ ] Cancel or compensate the reservation, regenerate, and confirm the quantity returns to the expected value.
- [ ] Disable one source, regenerate, and confirm its rows are removed without affecting the other source.
- [ ] Upload to a non-serving Local Inventory data source and record processing diagnostics.

Acceptance: source rows, store codes, quantities, availability, and parent or child modes match the recorded MSI state with no lost or duplicated rows.

## 9. Google Promotions

- [ ] Configure one eligible active cart rule and one ineligible rule.
- [ ] Save and reload all promotion fields. Confirm no values disappear or move to another feed.
- [ ] Set effective-from, effective-to, display-from, and display-to to distinct valid dates. Compare all four stored values after save, reopen, an unchanged save, and another reopen. Include month/day-ambiguous dates and year boundaries under the supported Admin locale.
- [ ] Verify browser serialization agrees with the provider's `Y/m/d` date contract and generated companion dates. A passing title/counter test does not establish date preservation.
- [ ] Generate the promotion file and confirm only eligible rules appear.
- [ ] Confirm promotion IDs in the Shopping feed match the generated promotion IDs.
- [ ] Use **Submit as new promotion** and confirm the intended ID changes exactly once.
- [ ] Confirm special-character and duplicate-word validation works in Admin.
- [ ] Confirm store, product, coupon, date, and redemption restrictions match the source rule.
- [ ] Test promotion date output when the store timezone is absent; 1.2.4 must use UTC rather than fail during generation.
- [ ] Upload to a non-serving Promotions data source and record diagnostics.

Acceptance: the promotion file and product promotion IDs agree, saved settings persist, and no inactive or ineligible rule is exported.

## 10. Scheduling, queue, and batch processing

- [ ] Set the store timezone to the recorded non-UTC test timezone.
- [ ] Add a schedule for the current store hour, save it, and run `bin/magento mage-os:shopping-feed:schedule -v`.
- [ ] Confirm exactly one eligible queue entry is created.
- [ ] Process or prepare another schedule earlier the same day, then change it to the current store hour and save it.
- [ ] Run the schedule command again. Confirm the edited schedule is eligible today and creates exactly one new queue entry.
- [ ] Run the schedule command again without another edit. Confirm it does not create a duplicate queue entry.
- [ ] Confirm a future schedule is not queued early.
- [ ] Confirm Magento cron skips disabled feeds and stops both module jobs when **Cron Enabled** is No. Test direct CLI commands separately: they use detached mode and still operate when module cron is disabled.
- [ ] Run `bin/magento mage-os:shopping-feed:generate` and confirm it processes the queue successfully.
- [ ] Enable batch mode with a small limit. Confirm offsets advance, the final file contains every expected row once, and completion status is correct.
- [ ] Interrupt a batch between chunks. Restart processing and confirm it resumes or restarts safely without a truncated accepted file.
- [ ] Run overlapping process attempts. Confirm locking prevents concurrent writers from corrupting the file or processing the same queue twice.
- [ ] Run the `mageos_shopping_feed` cron group for at least two complete hourly schedule cycles and monitor queue age, process state, and logs.

Acceptance: timezone handling, same-day edits, de-duplication, batch completion, and recovery behave predictably with no stuck queue or partial accepted file.

## 11. FTP, SFTP, and gzip delivery

Use isolated remote directories and filenames.

- [ ] Configure two upload rows with different credentials or destinations. Save and reload them.
- [ ] Obtain each SFTP server's SHA256 host-key fingerprint through a trusted channel. Save it in **SFTP Host Key Fingerprint** and confirm it survives reopen, unchanged save, and cloning.
- [ ] With a disposable SFTP fixture, test absent, malformed, and mismatched fingerprints. Confirm every case fails before a username or password is sent and makes no remote write; a confirmed matching key must permit delivery.
- [ ] Verify loaded/saved upload model data and database rows contain ciphertext, unchanged password masks retain it, and provider HTML/JSON exclude plaintext. A successful clone must preserve ciphertext and fingerprint; a forced clone-copy failure must roll back the new feed and child records.
- [ ] Confirm **FTP (unencrypted)** requires explicit selection and unknown upload modes fail without creating an FTP connection. Confirm connections close after success and failure.
- [ ] Confirm each row uses only its own credentials and remote path.
- [ ] Upload an uncompressed feed over FTP if FTP support is part of the release environment.
- [ ] Upload an uncompressed feed over SFTP.
- [ ] Enable gzip for each supported transport and upload again.
- [ ] Download each remote file, decompress when needed, and compare its checksum or parsed content with the accepted local file.
- [ ] Confirm gzip-enabled uploads use the expected remote filename and contain a valid gzip stream.
- [ ] Configure an invalid host, credential, and remote path separately. Confirm each fails clearly without deleting or replacing the last valid remote file.
- [ ] Confirm connection errors do not expose passwords, private keys, or full credentials in Admin messages, console output, or logs.
- [ ] Confirm temporary compressed files are removed after success and failure.
- [ ] Confirm retrying a failed upload does not generate duplicate remote submissions.

Acceptance: every enabled transport delivers the exact accepted payload, settings remain isolated per destination, and failure is safe and diagnosable.

## 12. Frontend integration

- [ ] Enable Google microdata for one store view and disable it for another.
- [ ] Open simple and configurable product pages in both store views.
- [ ] Confirm enabled pages contain one valid product and offer data set with correct SKU, currency, price, availability, and condition.
- [ ] Confirm disabled pages contain no module microdata.
- [ ] Change a configurable selection and confirm its deep link and selected offer data remain correct after page load.
- [ ] Enable Google Ads `view_item` output with a test destination ID.
- [ ] Confirm one event is emitted with the expected product identity and value.
- [ ] Confirm no direct `gtag` call occurs when the feature is disabled. The local custom event remains available to consent and tag-manager integrations.
- [ ] Confirm an empty destination omits `send_to` and lets the existing tag integration route the event; verify an explicit destination is isolated to its configured store view.
- [ ] Check the browser console, CSP report, page source, and structured-data validator for errors.
- [ ] Test Luma configurable and custom-option selection under the store's enforced CSP with the 1.2.4 secure-renderer templates. Record strict Hyvä CSP results separately; do not infer them from Luma or helper registration.
- [ ] Confirm full-page cache and Varnish do not leak one store view's currency, price, or feature state into another.

Acceptance: frontend output is store-scoped, valid, cache-safe, and absent when disabled.

## 13. Volume and operational behavior

Run with a catalog and configuration representative of the intended first production store.

- [ ] Record catalog size, eligible product count, feed row count, runtime, peak PHP memory, output size, and log size.
- [ ] Generate every enabled feed serially.
- [ ] Generate through cron and the queue using the intended batch size.
- [ ] Confirm runtime remains inside the store's cron window.
- [ ] Confirm memory remains below the configured limit with operational headroom.
- [ ] Confirm database connections, temporary storage, and disk space return to normal after completion.
- [ ] Confirm logs do not grow without rotation and do not contain repeated unexplained warnings.
- [ ] Rotate a pre-existing log larger than the configured threshold. Compare its decompressed gzip archive with the complete original by byte count and SHA256 before accepting truncation. Simulate archive creation/write/verification failure and confirm the original remains intact.
- [ ] Confirm generation does not materially degrade storefront response time, indexing, checkout, or other cron groups.
- [ ] Repeat an unchanged generation and confirm stable row count and stable identifiers.

Record the accepted operating envelope:

| Metric | Result | Accepted limit |
| --- | --- | --- |
| Eligible products | | |
| Output rows | | |
| Runtime | | |
| Peak memory | | |
| Output size | | |
| Peak temporary disk | | |

## 14. Failure recovery and rollback rehearsal

- [ ] Back up feed configuration and generated files before the rehearsal.
- [ ] Disable module cron and uploads.
- [ ] Stop an in-progress generation and identify its queue, process, temporary file, and log state.
- [ ] Follow the documented operator recovery, then regenerate and compare the accepted output.
- [ ] Disable `MageOS_ShoppingFeed`, clean caches, and confirm the storefront and Admin remain usable.
- [ ] Re-enable it, run `setup:upgrade`, and confirm saved configuration and accepted files remain intact.
- [ ] Confirm rollback does not require deleting shared files or altering an original Rocket Web module.
- [ ] Record the exact rollback commands, expected data retained, and restore point.

Acceptance: operators can stop new work, restore service, and recover feed generation without data loss or changes outside the module boundary.

## Defect handling

Record each failure with:

- Candidate commit and store profile
- Exact prerequisite and test step
- Expected and actual result
- Reproduction rate
- Relevant feed, SKU, source code, queue ID, and timestamps
- Sanitized logs, screenshots, generated row, and configuration
- Severity, owner, fix commit, and retest evidence

Severity rules:

- **High:** incorrect price, availability, identifier, destination, credential use, authorization, path containment, duplicate or missing submission, data loss, corrupted output, or inability to install, generate, schedule, upload, or recover.
- **Medium:** incorrect non-critical field, broken Admin workflow with a practical workaround, misleading status, recoverable retry defect, or material compatibility problem outside the minimum release path.
- **Low:** cosmetic issue, copy problem, or low-impact usability defect that does not threaten output correctness or operation.

No open High or Medium defect may be accepted for the first production release without an explicit written exception.

## Final release gates

- [ ] The exact candidate passes all automated checks and required GitHub jobs.
- [ ] Mage-OS 3.6.0 installation, compilation, and full functional acceptance pass. Record Mage-OS 3.5.0 CI coverage separately from any store acceptance repeated on that version.
- [ ] Generic, Google Shopping, Local Inventory, and Promotions outputs pass where configured.
- [ ] Local Inventory parent and associated quantities pass on real MSI data.
- [ ] Same-day schedule edits and duplicate prevention pass in the store timezone.
- [ ] Every enabled transport delivers an exact copy of the accepted payload.
- [ ] Merchant Center test destinations parse the applicable files without a module-caused error.
- [ ] Coexistence passes when an original Rocket Web module is in release scope.
- [ ] Volume testing establishes an accepted runtime and memory envelope.
- [ ] Rollback and recovery are rehearsed and recorded.
- [ ] No unresolved High or Medium defects remain.
- [ ] Release notes, installation instructions, license, provenance, and this plan match the candidate.
- [ ] A human reviewer approves the exact commit for the permanent repository.

## Sign-off

| Role | Name | Decision | Date | Evidence location |
| --- | --- | --- | --- | --- |
| Engineering | | Pass / Fail | | |
| Store operator | | Pass / Fail | | |
| Feed or marketing owner | | Pass / Fail | | |
| Release owner | | Approve / Reject | | |

Approval applies only to the recorded commit and tested configuration. Any code change after sign-off requires affected checks to be rerun and a new release decision.


## SHO-2 conditional values (unreleased)

Use synthetic products and a disabled feed with no schedule or uploads. Follow the [Conditional values guide](docs/wiki/Conditional-Values.md).

- In every feed type, add Columns Map and Replace Empty Values rows. Verify Static Value is the default, then explicitly select Conditional Value. Test both new rows and switching an existing saved mapping. Save and reopen; all controls and values must remain visible.
- Configure All and Any groups with a matching and a nonmatching product. Verify Fixed text, Product attribute, and Percentage adjustment outputs, including zero, empty strings, store-view option labels, and positive/negative adjustments.
- Test empty and nonnumeric percentage sources and the permitted percentage bounds. Verify invalid configuration is rejected rather than silently taking Otherwise; both branches must validate.
- Exercise condition option IDs, dates, missing values, and output transformations. Verify Replace Empty Values does not overwrite a populated column and respects replacement order.
- Test inheritance with parent/child fixtures. The Conditional Value editor creates product-scoped conditions; imported parent scopes use only the active mapping parent, without standalone parent lookup.
- Reject empty/oversized condition groups, unknown attributes, invalid operators, malformed imports, and incomplete outputs. Verify preserved structured values through export/import.
- Compare Test Feed results with complete generated output, including interaction with Product Selection and existing required-column filters.

The [editor-switch report](docs/reviews/2026-11-10-sho-2-editor-switch.md) records successful Admin switching, save/reopen, and generated outputs. The later [default-row report](docs/reviews/2026-11-10-sho-2-default-mapping.md) records unit and persistence coverage but leaves its browser save/reopen follow-up incomplete. Repeat that check before claiming complete browser acceptance of the default-row change.

## SHO-3 product selection (unreleased)

Use synthetic products and feeds without schedules or uploads. Existing filters must remain in effect.

- Confirm an existing feed without selection conditions exports the same rows.
- In Product Filters, add All/Any conditions on arbitrary product attributes, SKU wildcards, image presence, visibility, and parent status. Save, reopen, and check the exact condition scopes, operators, and values.
- Generate matching and nonmatching products. Check raw store-view values, missing attributes, option IDs, dates, multiselect values, wildcard case sensitivity, and blank/`no_selection` images.
- Check configurable, grouped, and bundle children independently of the parent row. A parent failing selection must not suppress a matching child. A rejected child must not be marked processed by duplicate tracking.
- For standalone rows, check enabled and disabled direct parents, multiple parents, and absent parents. All parent conditions must match the same parent; an active generation parent takes precedence over other links.
- Verify malformed saved/imported rules fail before persistence and malformed stored rules fail before generation, including an empty catalog. Remove all conditions and verify the filter is disabled.
- Combine SHO-3 selection and SHO-2 Conditional Value in one feed. Verify actual exported rows and values, preview, and a resumed batch.
- Confirm read-only roles cannot edit conditions and unavailable saved attribute choices remain visible for correction.

The [initial SHO-3 verification](docs/reviews/2026-11-10-sho-3-product-selection.md) is supplemented by the [independent-finding fixes and retest](docs/reviews/2026-11-10-sho-3-independent-fixes.md), covering persistence, malformed inputs, Unicode wildcards, date boundaries, parent contexts, skip counts, store isolation, and batching. These records distinguish completed local checks from untested platforms, recipient ingestion, and production workloads.

## SHO-5 per-column modifiers (unreleased)

Local checks and remaining limits: [SHO-5 verification](docs/reviews/2026-11-11-sho-5-modifiers.md).

- [ ] In Columns Map, add, reorder, delete, save, and reopen modifier rows. Preserve literal spaces, `0`, and `${...}` text; clear the complete list and reopen.
- [ ] Generate attribute, Static Value, Conditional Value, fallback, inherited child, and custom-option cells with multiple operations. Verify one application per final cell and each repeated value.
- [ ] Verify Unicode case changes, negative rounding/truncation, fixed decimal padding, dates with and without offsets, invalid and blank inputs, and feed/explicit price currency without currency conversion.
- [ ] Compare Strip tags and Escape HTML on the same markup, including script/style bodies. Reject unsafe unenclosed entity delimiters, including semicolon-space, `;|`, and `|&`, and verify enclosed semicolon output. Check paragraph, line-break, list, and comment word boundaries without entity decoding. Check TSV/CSV values, separators added by modifiers, and final output limits.
- [ ] Verify HTTP and protocol-relative links become HTTPS while paths/query strings remain unchanged. Confirm destination HTTPS independently.
- [ ] Reject malformed submitted rows, unknown operations/column names, invalid precision, and invalid currency arguments before persistence. Distinguish invalid-list messages from row-count messages. Confirm the read-only role cannot change modifier rows.

- [ ] Retest SHO-5 Google mappings: interleave modifiers on `promotions_id` and `promotion_id`, verify their combined sequence on `promotion_id`, and apply a modifier to runtime-added `color`.
- [ ] Reorder modifier rows using only the keyboard and the visible Order field. Save, reopen, and generate to prove the changed sequence. Check drag ordering and read-only permissions still work.

## SHO-1 XML output acceptance (unreleased)

Automated coverage includes XML parsing and escaping, repeated elements, CDATA terminators, namespaces, optional empty values, zero preservation, invalid characters, DTD rejection, generator row dispatch, batch boundaries, and UTF-8 filtering.

Local Admin, persistence, generation, and batch checks are recorded in the [November 10 retest](docs/reviews/2026-11-10-sho-1-independent-findings-fixed.md). Repeat these acceptance scenarios for the target installation:

1. Create a custom feed, select XML, set an `.xml` filename, and save a header, item element, footer, optional columns, and CDATA columns. Reload and verify every value.
2. Map a product containing ampersands, Unicode, and `]]>`; map a zero value, an empty optional value, and repeated image values. Generate and parse the downloaded file. Check values and tag counts.
3. Generate with one product per batch, then with no matching products. Verify exactly one header/footer and publication only after the final batch.
4. Publish a valid feed, then try saving malformed wrappers or an undeclared namespace. Save must fail. Separately inject invalid stored configuration in a disposable fixture; generation must fail and preserve the published file.
5. Run Test SKU; verify mapped values still render. Switch to delimited output, save/reload, and compare the generated CSV/TSV with the prior output.
6. Configure the same XML options in an extension preset and create a feed from it. Verify defaults survive save/reload and generation.

### SHO-1 destination XML presets

For each of Google Shopping, Google Local Inventory, Microsoft, Pinterest, TikTok, and Meta, select XML and an `.xml` filename, save/reload, and generate without entering wrappers or renaming columns. Verify RSS/channel/item structure, namespace-qualified fields, channel metadata from the selected store, and empty-value omission. Repeat on a feed with saved generic wrappers and confirm the canonical preset wins.

Verify shipping and tax nested groups for Google, Microsoft, Pinterest, and Meta, repeated additional images, TikTok's sku_id to g:id mapping, Local Inventory store codes and quantities, and unchanged delimited output when switching back. Confirm that only generic feeds show manual XML structure settings. OpenAI must not offer XML or generate it through a saved XML setting.

Local automated, installed-store, and Admin results are recorded in the [November 10 retest](docs/reviews/2026-11-10-sho-1-independent-findings-fixed.md). Meta and Pinterest namespace corrections were checked against provider samples. No preset has been submitted to a destination as part of local testing; TikTok XML field compatibility remains unverified. Recipient ingestion and production-volume acceptance are separate checks.

### SHO-1 independent-review regressions

- Put a single XML-invalid control character in one synthetic product description. Generate and preview: other products publish, skipped count increases, and Log Output identifies the SKU and column. Repeat with a bad product in the last cron batch.
- Use a shipping service title containing both a comma and colon. Verify one intact nested service value, including persistent-cache reuse and switching formats.
- Verify that an additional-image URL containing a comma remains one URL in XML.
- Edit and save multiline XML header/footer fields in Admin. Verify exact stored newlines and successful generation.
- Reject invalid element names, undeclared prefixes, malformed wrappers, DTDs, and whitespace before the declaration on save; accept valid Unicode names.
- Toggle XML/Delimited and Other Delimiter; show only applicable settings. OpenAI must not offer XML, including with a historical invalid stored selection.
- Compare Meta/Pinterest namespaces to the provider samples; verify configured Local Inventory pickup-cost nesting without inventing merchant values.

See [the 2026-11-10 fixes and retest report](docs/reviews/2026-11-10-sho-1-independent-findings-fixed.md) for executed results and boundaries.
