# Mage-OS Shopping Feed

**Version 1.2.5** creates the **Skip from Being Submitted** product attribute, so a product can be left out of feeds per store view, and reads dropdown and multiselect option labels for the feed's store view. Read [Release 1.2.5](Release-1-2-5) and its [upgrade checklist](Installation-and-Upgrade#upgrading-from-124-to-125). [Release 1.2.4](Release-1-2-4) added SFTP server-key verification, encrypted upload credentials, safe log rotation, and Test Feed permissions.

**Migrating from Rocket Shopping Feeds?** Use the separate [Rocket Web migration companion](https://github.com/rocketweb/module-shopping-feed-migration-rocketweb). See the [installation and migration guide](Rocket-Web-Migration) for preview, import, output comparison, activation, and rollback.

[![Check Extension on main](https://github.com/mage-os-lab/module-shopping-feed/actions/workflows/check-extension.yaml/badge.svg?branch=main)](https://github.com/mage-os-lab/module-shopping-feed/actions/workflows/check-extension.yaml?query=branch%3Amain)

Version 1.2.0 introduced UI Component forms, five catalog presets, stock and Local Inventory corrections, store-scoped frontend settings, and currency preservation. Read [Release 1.2.0](Release-1-2-0) and its [upgrade checklist](Installation-and-Upgrade#upgrading-from-11-to-120). [Version 1.1.0](Release-1-1-0) remains documented for existing installations.

Mage-OS Shopping Feed generates product feeds from Mage-OS and Magento Open Source. It combines generic product feeds, Google Shopping, Google Local Inventory, and Google Promotions in one independently named module.

The [1.1 upgrade checklist](Installation-and-Upgrade#upgrading-from-10-to-11) covers the required schema update and changes to saved Google and comma-delimited feeds. [Release 1.0.0](Release-1-0-0) remains available as a historical record.

> Documentation baseline: 1.3.0 release candidate. Last reviewed: 2026-11-12.

The [Admin UI Component forms](Admin-UI-Component-Forms) in 1.2.0 change the editor and its customization hooks. The historical 1.2.0 runtime `133af71` was verified on `mageos-latest`, with form, grid-permission, preview, stock-selection, Local Inventory, frontend-scope, and currency-preservation corrections. Magento Open Source 2.4.8 and 2.4.9 retain production-mode form/output and six-role acceptance. The repository's `docs/reviews/2026-10-03-local-acceptance.md` records that historical acceptance, including extended operational checks and remaining limits. Historical version 1.1 continues to use the previous editor.

Magento Open Source 2.4.7-p10 has a separate compatibility profile and upstream Flysystem advisory. Review [Status and compatibility](Status-and-Compatibility) before installing on that version; the tested optional backport is not applied automatically.

The 1.2.3 hardening and native-theme reports cover all eight presets, reservation-aware aggregate stock, promotion output, manual queue recovery, Hyvä selections/cart, and Nebula filters, actions, and permissions. Editor, preview, and log pages use the supported Magento backend fallback. See [Release acceptance](Release-Acceptance) for the evidence and remaining limits.

Version 1.2.4 is [published](https://github.com/mage-os-lab/module-shopping-feed/releases/tag/v1.2.4) at `75dd335f930aef78ae693f62c3039e469c2d26f6`. All 37 [post-merge CI jobs](https://github.com/mage-os-lab/module-shopping-feed/actions/runs/39372608509) passed for that commit. Its Mage-OS 3.6 local acceptance is separate from the older theme checks below; see [Release acceptance](Release-Acceptance).

**Upgrading:** run `setup:upgrade`. In 1.2.5 it creates the exclusion attribute. From 1.2.3 or earlier, also enter a trusted fingerprint for every SFTP destination before resuming transfers. Test Feed requires **Generate Feed** permission since 1.2.4.

**OpenAI beta:** the bundled Google-compatible preset differs from the current public OpenAI product-feed contract. Confirm acceptance of an actual sample before use; see [the documented format gaps](OpenAI-ChatGPT).

## Unreleased features

[Conditional Value](Conditional-Values) chooses a column value using product conditions. [Product Selection](Product-Selection) filters whole output products using attribute conditions. Both are available across all eight feed types in this branch and are not included in 1.2.5. The guides cover setup, parent context, examples, and verification.

## Unreleased XML output

[XML Output](XML-Output) adds automatic presets for Google Shopping, Google Local Inventory, Microsoft, Pinterest, TikTok, and Meta. Generic feeds support manual XML configuration. The guide covers switching formats, filenames, optional tags, CDATA, and validation. This feature is not included in 1.2.5.

## What the module does

The module gives a merchant control over which products enter a feed, how Magento data maps into columns, how complex products are represented, and when completed files are transferred.

Current capabilities include:

* Generic delimited product feeds
* Meta, Microsoft, TikTok, and Pinterest catalog presets, plus an OpenAI Google-compatible beta with onboarding confirmation
* A Google Shopping template with current item-group and variant columns
* Google Local Inventory output with optional Multi-Source Inventory support
* Google Promotions generated alongside a Google Shopping feed
* Category filtering and Google taxonomy mapping
* Column mapping, static values, transformations, and output limits
* Configurable, grouped, bundle, and custom-option handling
* Scheduled and manual generation through a dedicated queue
* FTP (unencrypted) or SFTP delivery with a required trusted SHA256 host-key fingerprint, plus optional streaming gzip compression
* Storefront schema.org offer data for automatic product updates
* Configurable-product deep links and Google Ads `view_item` events

## Start here

New installation:

1. Read [Status and compatibility](Status-and-Compatibility).
2. Follow [Installation and upgrade](Installation-and-Upgrade).
3. Build and test one feed with [Quick start](Quick-Start).
4. Configure the feed in detail using the Configuration section in the sidebar.
5. Complete the [release acceptance](Release-Acceptance) checks before enabling production schedules or uploads.

Existing Rocket Web installation:

1. Read [Migration and coexistence](Migration-and-Coexistence) first.
2. Follow the [optional migration companion guide](Rocket-Web-Migration) on staging with schedules and uploads disabled.
3. Preview and explicitly import one feed, or recreate it manually. Compare actual output before planning production cutover.

## Admin locations

Manage feeds at **Catalog > Mage-OS Shopping Feed > Feeds Management**.

Global settings are at **Stores > Configuration > Mage-OS > Mage-OS Shopping Feed**.

## A necessary boundary

This module has its own Composer package, Magento module, PHP namespace, database tables, configuration paths, Admin route, cron group, events, logs, and output directory. It does not replace or migrate an installed Rocket Web package.

## Documentation publication

These pages are maintained in the module repository under `docs/wiki` and copied into this separate GitHub Wiki repository after review. A module commit, merge, or release does not automatically synchronize the wiki.

## Getting help

For setup and troubleshooting, use the [Shopping Feed support bot on Rocket Web](https://rocketweb.com/mage-os-shopping-feeds). Select **Open support chat** on the product page.

Use [GitHub Issues](https://github.com/mage-os-lab/module-shopping-feed/issues) for reproducible defects and feature requests. Do not post credentials, private feed files, customer data, or internal paths.

Report security issues through the repository's [private vulnerability reporting process](https://github.com/mage-os-lab/module-shopping-feed/security/policy).
