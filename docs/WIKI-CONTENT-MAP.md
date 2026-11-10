# Wiki content map

This map records how the historical Rocket Web documentation is treated in the Mage-OS Shopping Feed wiki. It is topic-based because several old pages are merged into one current operating guide.

| Historical subject | Current destination | Treatment |
| --- | --- | --- |
| Product overview and supported feeds | `Home`, `Status-and-Compatibility`, `Feed-Types-and-Lifecycle` | Rewritten for the consolidated Mage-OS module |
| Installation and Magento Marketplace access | `Installation-and-Upgrade` | Replaced with the current package identity and source-install option |
| Creating and managing feeds | `Quick-Start`, `Feed-Types-and-Lifecycle`, `Admin-UI-Component-Forms` | Current and historical editor behavior is distinguished; acceptance limits and customization migration are explicit |
| General feed settings and output files | `General-Configuration`, `Commands-Paths-and-Settings` | Updated for safe output paths, current filenames, currency, delimiter, and stock behavior |
| XML output and six channel presets (unreleased SHO-1) | `XML-Output` | Channel setup, generic wrappers, optional tags, CDATA, examples, and validation |
| Column mapping and directive reference | `Columns-and-Directives` | Consolidated and checked against current feed configuration |
| Categories and Google taxonomy | `Categories-and-Taxonomy` | Retained, with current terminology and verification guidance |
| Filters, find-and-replace, and price buckets | `Filters-and-Transformations` | Consolidated and reframed as ordered data transformations |
| Custom options | `Product-Options` | Updated for current option handling |
| Configurable, grouped, and bundle products | `Complex-Products` | Consolidated with current parent and child behavior |
| Shipping calculation | `Shipping` | Retained with performance and validation boundaries |
| Generic feeds | `Generic-Feeds` | Updated for the current template and directives |
| Google Shopping setup | `Google-Shopping` | Updated for current bundled columns and official Google specifications |
| Google Local Inventory | `Google-Local-Inventory-and-MSI` | Expanded for website stocks, source mapping, reservations, and source-level quantity |
| Google Promotions | `Google-Promotions` | Rewritten as a companion of Google Shopping with current destinations and IDs |
| Automatic item updates | `Automatic-Updates-and-Schema-org` | Rewritten for current schema.org output and Google terminology |
| Configurable-product URLs | `Configurable-Product-Deep-Links` | Updated for query tracking, fragments, swatches, and select controls |
| AdWords and DoubleClick remarketing | `Google-Ads-View-Item-Events` | Replaced with current `view_item` event behavior and consent boundary |
| Manual generation and command line | `Manual-and-CLI-Generation`, `Testing-One-Product` | Replaced with the `mage-os:shopping-feed:*` commands and current test behavior |
| Schedules and custom cron scripts | `Scheduling-and-Queues` | Rewritten for the dedicated cron group, queue ordering, and batch continuation |
| FTP uploads | `Uploads` | Expanded for SFTP, encrypted stored passwords, gzip, and quarantine testing |
| Logs, stalled generation, and common errors | `Logs-and-Troubleshooting` | Rewritten for current paths, lock semantics, atomic output, and evidence collection |
| Large-catalog optimization | `Performance-and-Large-Catalogs` | Rewritten around measured workload and current batch behavior |
| QA checklists | `Release-Acceptance` and repository `ACCEPTANCE-TEST-PLAN.md` | Replaced with release-candidate evidence and safe external boundaries |
| Pricing, licensing, private support, and marketing pages | Repository license, releases, security policy, and project governance | Excluded from operating documentation unless a current public source supports the claim |
| Release notes and acceptance | current `Release-1-2-5`, historical `Release-1-2-4`, historical `Release-1-2-3`, historical `Release-1-2-2`, `Release-1-2-1`, `Release-1-2-0`, historical `Release-1-1-0`, historical `Release-1-0-0`, `docs/releases`, and GitHub Releases | Wiki summaries link to versioned release notes and acceptance evidence; CHANGELOG remains the full change list |

Historical pages remain useful for discovering user questions, but they are not authoritative for commands, package names, requirements, paths, Google interfaces, or present behavior.
