# Feed types and lifecycle

The feed type provides a starting column map and default behavior. All feeds then move through the same save, test, queue, generation, upload, and review lifecycle.

> Documentation baseline: 1.3.0 release candidate. Last reviewed: 2026-11-12.

The [UI Component editor](Admin-UI-Component-Forms) uses collapsible sections instead of the 1.1 tabs. Its category and promotion-date defects are repaired and verified locally. The linked guide records the tested runtime, currency preservation, and remaining integration limits.

Version 1.2.0 includes four additional catalog presets: [Meta Catalog](Meta-Catalog), [Microsoft Merchant Center](Microsoft-Merchant-Center), [TikTok Catalog](TikTok-Catalog), and [Pinterest Catalog](Pinterest-Catalog). Version 1.2.0 also adds the [OpenAI / ChatGPT Google-compatible beta](OpenAI-ChatGPT). All additions require destination validation before production use.

## Feed types

| Feed | Use it for |
| --- | --- |
| Generic | Custom feeds for systems without a bundled template |
| Google Shopping | Google product data, variants, taxonomy, shipping, and product-linked promotion IDs |
| Google Local Inventory | Store-level availability, quantity, and price, optionally expanded by MSI source |
| Google Promotions | A companion file configured and generated from a Google Shopping feed |
| Meta Catalog (1.2.0) | Facebook and Instagram product catalogs using a scheduled TSV data feed |
| Microsoft Merchant Center (1.2.0) | Microsoft Shopping product catalogs using tab-delimited TXT |
| TikTok Catalog (1.2.0) | TikTok Ads Manager product catalogs using a scheduled CSV feed |
| Pinterest Catalog (1.2.0) | Pinterest retail catalogs and product Pins using a scheduled TSV feed |
| OpenAI / ChatGPT (Google-compatible, beta, 1.2.0) | Historical TSV discovery preset; current public OpenAI format differs. Explicit sample acceptance is required |

See the individual feed guides before relying on their default columns. The TSV/CSV/TXT descriptions above are the default delimited formats. Version 1.3.0 adds automatic XML presets for Google Shopping, Google Local Inventory, Microsoft, Pinterest, TikTok, and Meta, plus manual XML for Generic. See the [XML format table and setup](XML-Output). OpenAI remains delimited only, and Google Promotions keeps its separate companion format.

These templates generate advertising and catalog feeds. Marketplace listing management and order synchronization for Amazon, Walmart, or eBay require separate integrations.

## Grid actions

The Feeds Management grid exposes these per-feed actions:

* **Configure** opens the feed editor.
* **Run Now** adds the feed to the generation queue.
* **Test Feed** requires **Generate Feed** permission and renders one SKU or product ID without replacing the normal production file.
* **View Log** opens the per-feed log.

The grid also supports mass enable, disable, clone, and delete operations when the Admin role has the required ACL permissions. In the standard grid, mass **Delete** executes immediately without a confirmation dialog. Verify the selected names and count before choosing it. Individual **Delete Feed** in the editor has a confirmation dialog.

## Statuses

| Status | Meaning |
| --- | --- |
| Disabled | The feed is not active for normal operation. |
| Scheduled | The feed is enabled and idle. New feeds and feeds enabled from the grid start here, whether or not they have schedule rows; cron skips only disabled feeds. |
| Pending | Work is waiting in the generation queue. |
| Processing | A worker is generating the feed. |
| Completed | The last generation completed. Review the file and counts. |
| Error | Processing failed. Open the feed log before retrying. |

## Recommended lifecycle

1. Create the feed from the closest template.
2. Save its general settings and column map.
3. Test representative products.
4. Generate the complete file without an upload destination.
5. Compare product counts and sampled data with Magento.
6. Validate the file with the intended recipient.
7. Add one non-serving upload destination and test it.
8. Add schedules only after the output and transfer are accepted.
9. Record the accepted commit, configuration, file checksum, and external result.

## Clone carefully

Cloning creates a new disabled feed with an `_clone` name and copies its configuration, schedules, and upload destinations. It does not guarantee a distinct output filename: a literal filename is copied unchanged and can overwrite the source feed's file when the clone is generated. A filename containing `%s` substitutes the new feed ID. Review the filename, store view, schedules, and destinations before enabling or generating the clone. This behavior predates the UI Component migration.
