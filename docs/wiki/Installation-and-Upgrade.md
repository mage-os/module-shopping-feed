# Installation and upgrade

Install the module on staging first. Feed generation writes files and records queue, schedule, upload, and status data. A saved upload can also transfer completed files to an external system.

> Documentation baseline: 1.3.0 release candidate; historical checklists retain their versions. Last reviewed: 2026-11-12.

Magento Open Source 2.4.7-p10 has a separate compatibility profile and upstream Flysystem advisory. Review [Status and compatibility](Status-and-Compatibility) before installing on that version; the tested optional backport is not applied automatically.

Upgrade checklists are cumulative. If skipping releases, review every section between the installed and target versions. Historical Composer constraints such as `^1.2.1` also allow later 1.x versions; check the resolved lock file and follow the requirements of the version actually installed. This branch prepares 1.3.0; use its installation commands only after the tag is published and indexed.

## Upgrading to 1.3.0

The [1.3.0 candidate](Release-1-3-0) adds XML, conditional values, product selection and modifiers without new schema/data patches or changed platform requirements. It has not been tagged or published yet.

1. Back up code, database, Composer lock, feed configuration and accepted files. Record schedules and destinations.
2. After publication, update staging with `composer require 'mage-os/module-shopping-feed:^1.3.0' --no-update`, `composer update mage-os/module-shopping-feed --with-dependencies`, `bin/magento setup:upgrade`, and the store's normal compilation/static-content/cache steps.
3. Compare complete outputs for existing feeds. Test new conditions, modifiers and XML on disabled copies, including save/reopen, preview, full generation and batches.
4. Selecting XML does not rename the file. Choose an `.xml` name and validate the recipient format before changing its URL or enabling delivery.
5. Before a downgrade, restore pre-upgrade configuration or remove new rules/directives/modifiers and switch back to delimited output. Older versions cannot be assumed to interpret new settings. Restore matching code/lock and database backups with schedules/uploads disabled, then verify output.

The older checklists below are cumulative: pre-1.2.5 upgrades need the product exclusion attribute; pre-1.2.4 SFTP destinations need trusted fingerprints. See the [full notes](https://github.com/mage-os/module-shopping-feed/blob/release/1.3.0/docs/releases/1.3.0.md).

## Upgrading from 1.2.4 to 1.2.5

The [1.2.5 release](Release-1-2-5) creates the **Skip from Being Submitted** product attribute during `setup:upgrade` and reads dropdown and multiselect option labels for the feed's store view. Platform and Composer requirements are unchanged from 1.2.4, and there are no table changes.

1. Back up matching code, database, Composer lock file, configuration, and accepted feed files.
2. On staging, run `composer require 'mage-os/module-shopping-feed:^1.2.5' --no-update`, then `composer update mage-os/module-shopping-feed --with-dependencies` and `bin/magento setup:upgrade`. Follow the store's normal compilation, static-content deployment, and cache process.
3. Open a product in Admin and confirm the **Shopping Feed** section shows **Skip from Being Submitted** with the default No. A store that already had a `mageos_shopping_feed_skip_submit` attribute keeps it as defined; confirm it is a Yes/No field, because the exclusion honors only the value 1.
4. Regenerate one feed per store view and compare with the accepted output. Dropdown and multiselect columns on non-default store views now carry that store view's option labels where an override exists. Products set to Yes are absent and named in the feed log.
5. Run `setup:upgrade` a second time and confirm nothing changes.

Restore the matching database, code, lock file, and configuration for rollback. The created attribute and any saved values stay in the database after a code rollback, and 1.2.4 still honors a Yes value because it already checked the attribute.

## Upgrading from 1.2.3 to 1.2.4

The [1.2.4 release](Release-1-2-4) adds a required SFTP host-key fingerprint and a nullable database column for it. It also adds an explicit `phpseclib/phpseclib:^3.0` dependency and declares `magento/framework:^103.0.6-p15`. Check those requirements against the target store before updating Composer.

1. Back up matching code, database, Composer lock file, configuration, and accepted feed files. Pause scheduled uploads while reviewing destinations.
2. On staging, run `composer require 'mage-os/module-shopping-feed:^1.2.4' --no-update`, then `composer update mage-os/module-shopping-feed --with-dependencies` and `bin/magento setup:upgrade`. Follow the store's normal compilation, static-content deployment, and cache process.
3. Obtain each SFTP server's trusted `SHA256:...` host-key fingerprint from its administrator. Enter it in **Uploads > SFTP Host Key Fingerprint**, save, and reopen. A missing or mismatched fingerprint prevents authentication. See [Uploads](Uploads).
4. Verify a masked password survives an unchanged save, clone destinations where used, and compare complete generated and delivered files with the accepted output. FTP is explicitly unencrypted; unknown transfer modes are rejected.
5. Verify Test Feed access with a role that has feed-generation permission. Compare inventory, configurable shipping weights, and promotion output before resuming schedules or uploads.

Restore the matching database, code, lock file, and configuration for rollback. An older release does not provide SFTP host-key verification.


## Upgrading from 1.2.2 to 1.2.3

The [1.2.3 release](Release-1-2-3) changes reservation-aware aggregate quantities and physical-source Local Inventory output, repairs promotion settings/cache writes and queue checkpoints, and preserves Nebula column filters through paging and sorting. It adds no schema change or required Composer dependency.

Back up code, database, Composer lock file, and accepted feed files. On staging, run `composer require 'mage-os/module-shopping-feed:^1.2.3' --no-update`, then `composer update mage-os/module-shopping-feed --with-dependencies` and the store's normal Magento deployment process. Regenerate representative aggregate and Local Inventory feeds across linked stocks/sources and reservations. Compare promotion settings, product IDs, TSV rows, manual batching, and storefront microdata before resuming schedules or uploads.

Composer installations display their package version in Admin. Source installations can still show the schema version as a fallback. The 1.2.0 editor customization requirements below continue to apply.

## Upgrading from 1.2.1 to 1.2.2

The [1.2.2 release](Release-1-2-2) fixes description cleanup for stray attribute quotes. It adds no schema change or required dependency relative to 1.2.1.

Use `composer require 'mage-os/module-shopping-feed:^1.2.2' --no-update`, then `composer update mage-os/module-shopping-feed --with-dependencies` and the store's normal Magento deployment process. On staging, regenerate descriptions containing media directives, inch marks, escaped HTML, and configured length limits. Compare the complete recipient file before resuming delivery.

Users moving from Rocket Shopping Feeds should also read the [separate migration companion guide](Rocket-Web-Migration). Main-module upgrades do not import legacy records.

<a id="upgrading-from-120-to-121-candidate"></a>

## Upgrading from 1.2.0 to 1.2.1

The [1.2.1 release](Release-1-2-1) corrects required-option pricing, inherited/configurable backorders, and Page Builder description cleaning. It adds no schema change or required dependency relative to 1.2.0.

Back up the matching code, database, Composer lock file, and generated output. On staging, compare free and percentage-based required options, backordered products and their availability dates, configurable parents/children, and markup-heavy descriptions with configured length limits. Regenerate feeds and check actual recipient output before resuming delivery. The 1.2.0 editor customization requirements below still apply when upgrading from 1.1.

Select `mage-os/module-shopping-feed:^1.2.1` through the store's normal Composer and Magento deployment process. The optional [Rocket Web migration companion](Rocket-Web-Migration) has its own installation and release; upgrading the main module does not import legacy records.

<a id="upgrading-from-11-to-the-120-candidate"></a>

## Upgrading from 1.1 to 1.2.0

[Version 1.2.0](Release-1-2-0) changes the default feed editor and adds five presets. It has no new schema change or Composer platform requirement relative to 1.1.0. Local acceptance covers Magento Open Source 2.4.7-p10, 2.4.8, 2.4.9, and Mage-OS 3.5.0 within their recorded scopes.

Before upgrading staging:

1. Back up the matching code, database, Composer lock file, feed configuration, and output. Pause schedules and uploads during comparison.
2. Audit custom PHP form observers, tab plugins, and directive parameter renderers. Migrate them to UI metadata or components using [Admin UI Component forms](Admin-UI-Component-Forms). The module's own integrations are already converted; this step applies to site-specific customizations.
3. Update explicit constructor overrides for the feed-action column's added authorization dependency and the `Composite`/`Configurable` adapters' linked-product collection factory.
4. Review existing website/store overrides for microdata and Google Ads settings, which now take effect. Clear applicable caches.
5. Deploy the reviewed release and rebuild DI and Admin static assets through the store's normal process. Run `setup:upgrade`, especially if also crossing the 1.0-to-1.1 schema change.
6. Save and reopen disabled copies, then compare preview and complete output. Check currency, null directive parameters, category IDs, promotion dates, stock, prices, restricted roles, and password masks. Test each actual recipient before resuming delivery.

For the historical 1.2.0 target, use `composer require 'mage-os/module-shopping-feed:1.2.0' --no-update` followed by `composer update mage-os/module-shopping-feed --with-dependencies` and the normal Magento deployment steps. Source installations targeting that historical release use `v1.2.0`. To move directly to current 1.2.4, also complete the SFTP, schema, and permission checklist above; a `^1.2` constraint can select 1.2.4 or a later 1.x release.

There is no old/new editor switch. Restoring the previous package and rebuilding DI/static/cache restores the old editor, but does not undo later configuration saves or recover dates erased by an earlier development candidate. Restore matching configuration from the pre-upgrade backup where necessary. The earlier category/date defects are repaired; see [Release acceptance](Release-Acceptance) for current evidence and limits.

## Before installation

1. Confirm the target Mage-OS or Magento Open Source and PHP versions are supported.
2. Record the exact module commit or package version.
3. Back up the database and relevant media and configuration files.
4. Confirm Magento cron is healthy.
5. If a Rocket Web feed package is installed, read [Migration and coexistence](Migration-and-Coexistence).
6. Plan to keep new schedules and uploads disabled until a generated file has been reviewed.

## Composer installation

The package is listed on [Packagist](https://packagist.org/packages/mage-os/module-shopping-feed). Install the stable 1.2 line:

```bash
composer require 'mage-os/module-shopping-feed:^1.3.0'
bin/magento module:enable MageOS_ShoppingFeed
bin/magento setup:upgrade
bin/magento cache:clean
```

In production mode, complete the normal deployment steps for the project, including dependency-injection compilation and static-content deployment where required.

When updating Admin JavaScript or templates in an existing deployment, back up and clear this module's published `MageOS_ShoppingFeed` directories for each deployed Admin theme and locale, together with its matching files under `var/view_preprocessed`. Rebuild Admin static content and verify the published files match `view/adminhtml/web` in the installed package. An overlay of vendor files alone can leave an older editor in place even when static deployment reports success. Reload the Admin page after deployment.

## Source installation

Place or symlink the source at:

```text
app/code/MageOS/ShoppingFeed
```

After publication, use the `v1.3.0` tag for a reproducible installation, and record its resolved commit. Earlier tags remain available for historical installations.

A source copy does not install its Composer dependencies. Compare the application's installed packages with the tag's `require` section, including `phpseclib/phpseclib:^3.0`, and install missing compatible runtime dependencies through the application's root Composer project. Do not also install a second copy of `mage-os/module-shopping-feed`.

Then run:

```bash
bin/magento module:enable MageOS_ShoppingFeed
bin/magento setup:upgrade
bin/magento cache:clean
```

## Verify the installation

```bash
bin/magento module:status MageOS_ShoppingFeed
bin/magento list mage-os:shopping-feed
```

Then verify:

* **Catalog > Mage-OS Shopping Feed > Feeds Management** opens for an authorized administrator.
* **Stores > Configuration > Mage-OS > Mage-OS Shopping Feed** is available.
* The `mageos_shopping_feed_*` tables exist.
* The Admin and storefront load without new PHP or JavaScript errors.
* `bin/magento setup:di:compile` succeeds in the intended production configuration.

## Upgrade

Upgrade through the same installation path used for the module:

1. Record the current and target versions or commits.
2. Back up the database and generated feed configuration.
3. Update the Composer package or source checkout.
4. Run `bin/magento setup:upgrade`.
5. Clean the required caches and complete the project's deployment steps.
6. Regenerate a non-production feed and compare it with the previous accepted output.
7. Re-enable schedules and uploads only after the new output is accepted.

## Upgrading from 1.0 to 1.1

This is the historical 1.1 procedure. The `^1.1` constraint below can resolve a newer 1.x release, including 1.2.5. For a current upgrade, also follow every applicable later checklist, especially the required SFTP fingerprint and `setup:upgrade` step.

Version 1.1.0 requires `setup:upgrade`: encrypted upload passwords now use a `text` column, and standard-grid keyword search uses a full-text index on feed names. Complete DI compilation and static-content deployment where your deployment process requires them, then clear caches so the corrected Admin JavaScript loads.

For a Composer installation:

```bash
composer require 'mage-os/module-shopping-feed:^1.1' --no-update
composer update mage-os/module-shopping-feed --with-dependencies
bin/magento setup:upgrade
bin/magento cache:clean
```

An existing development-branch installation can move to the same stable constraint. Record the old lock file, module code, database, and configuration before upgrading. A rollback should restore that matching set; reverting PHP alone does not reverse schema and saved-configuration changes.

Review upload destinations by saving and reopening them with the password mask unchanged. If an old credential cannot be decrypted, replace it. The module does not recover secrets from truncated ciphertext. Verify schema status with `bin/magento setup:db:status` and test standard-grid keyword search.

Test scheduled recovery, selected promotion rules, UTF-8 limits, and storefront microdata/deep links where enabled. Nebula remains optional: test the grid and editor under the Admin theme actually used by the store. Historical JSON-looking text configuration keeps its explicit string marker; older development decoders require a compatible backup when rolling back.

## Google and custom-CSV changes in 1.1

Review saved feeds before resuming uploads:

1. **Google identifiers:** existing mappings are preserved. Replace SKU-to-MPN mappings unless SKU is the actual manufacturer MPN. Map real GTIN/MPN attributes. The Identifier Exists directive no longer writes FALSE merely because required fields are empty; confirmed identifier absence is now an explicit choice.
2. **Google backorders/preorders:** add `availability_date` to existing feeds and map a real expected shipping date. Rows requiring a date are skipped with a log warning until the date is valid. New feeds contain an empty placeholder, not an invented date.
3. **Local Inventory:** zero-stock backorders export as `out_of_stock`. Disabled source items also export as out of stock. Compare local quantities and statuses with the stores they represent.
4. **Generic CSV:** comma-delimited feeds now preserve commas and use CSV quotation rules for headers and values. Test the recipient's CSV parser; line splitting is not sufficient. Tab and other custom delimiters retain their existing behavior when no enclosure is set.

5. **Saved output settings:** the declared `output_params_enclose_cell`, `output_params_enclose_escape`, and `output_params_default_value` settings now take effect. Inspect any values set by scripts or imports. Generic feeds expose these controls in the General section.
6. **Pricing and variants:** compare guest quantity-one tier prices with the storefront, check custom Tier Price columns for previous bulk-only prices, and enable Complex Product Context Prioritization when visible children must retain their parent's grouping. Search-only children are now included in that setting.

The Google and CSV changes do not overwrite saved column maps or need their own data migration. The 1.1 release still requires the schema upgrade described above. See [Google Shopping](Google-Shopping), [Local Inventory](Google-Local-Inventory-and-MSI), and [Generic feeds](Generic-Feeds) for setup and examples.

## Do not copy old installation instructions

Instructions for `rocketweb/module-google-shopping`, Magento Marketplace access keys, `rocketshoppingfeed:*` commands, or a `pub/media/feeds` output directory belong to the older Rocket Web packages. They do not install or operate `MageOS_ShoppingFeed`.
