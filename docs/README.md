# Documentation

These guides cover released behavior through 1.2.5 and explicitly marked unreleased features on this branch. Check each page's documentation baseline and review date before applying it to an installed version. Dated verification reports describe their tested revision; documentation commits do not publish a package release or synchronize the separate public wiki.

## Current guides

| Task | Guide |
| --- | --- |
| Install or upgrade | [Installation and upgrade](wiki/Installation-and-Upgrade.md) |
| Review 1.2.5 changes | [Release notes](releases/1.2.5.md) and [changelog](../CHANGELOG.md) |
| Confirm platform and theme support | [Status and compatibility](wiki/Status-and-Compatibility.md) |
| Create and operate feeds | [Wiki home](wiki/Home.md), [Quick start](wiki/Quick-Start.md), and the wiki sidebar |
| Configure conditional output (unreleased SHO-2) | [Conditional values](wiki/Conditional-Values.md) |
| Select products by attribute rules (unreleased SHO-3) | [Product selection](wiki/Product-Selection.md) |

| Configure XML and channel presets (unreleased SHO-1) | [XML output](wiki/XML-Output.md) |
| Configure SFTP, FTP, or gzip | [Uploads](wiki/Uploads.md) |
| Move from Rocket Shopping Feeds | [Migration and coexistence](../MIGRATION.md) and [optional companion](wiki/Rocket-Web-Migration.md) |
| Customize the Admin editor | [Developer contract](ui-component-editor.md) |
| Run development checks | [Contributing](../CONTRIBUTING.md) and [Development and CI](wiki/Development-and-CI.md) |
| Validate a target store | [Acceptance plan](../ACCEPTANCE-TEST-PLAN.md) and [release evidence](wiki/Release-Acceptance.md) |
| Evaluate Magento 2.4.7-p10 | [Dependency guidance and optional backport](compatibility/magento-2.4.7-p10.md) |
| Maintain or publish the wiki | [Maintenance workflow](WIKI-MAINTENANCE.md) and [content map](WIKI-CONTENT-MAP.md) |
| Review this documentation refresh | [October 20 review](reviews/2026-10-20-documentation-review.md) |
| Compare with paid feed extensions | [Competitive gap analysis](plans/2026-11-09-competitive-gap-analysis.md) |

## Upgrade requirements and limits

Version 1.2.4 requires `setup:upgrade` and a trusted SHA256 host-key fingerprint for every SFTP destination before authentication. Test Feed now requires **Generate Feed** permission. Upload model passwords remain encrypted; custom transport code must request decryption explicitly. See the upgrade and developer guides before resuming transfers.

The known preset output-limit key mismatch remains in 1.2.4. Set explicit **Limit column output** rows. Meta does not reject overlong text locally; other catalog validators have their own length checks. Generated feed files remain publicly accessible under the configured media directory.

The [OpenAI Google-compatible beta](wiki/OpenAI-ChatGPT.md) differs from OpenAI's current public contract, including seller data, URLs, variants, and preorder values. The preset has not been updated to that contract. Confirm the exact sample with OpenAI before enabling any transfer.

## Evidence and history

The [October 20 Mage-OS 3.6 verification](reviews/2026-10-20-standards-security-mageos36.md) records local 1.2.4 acceptance. All 37 [post-merge CI jobs](https://github.com/mage-os-lab/module-shopping-feed/actions/runs/39372608509) passed for the tagged commit. Earlier reports under `reviews/`, the editor acceptance report, and plans under `plans/` are dated snapshots. Their test counts, deployment state, and remaining work describe their recorded revision, not the present state of a store.

Historical release notes remain under `releases/`; they do not replace the cumulative current upgrade checklist. Native Nebula UI Bridge rendering, strict Hyvä CSP themes, external recipient ingestion, and production capacity remain separate acceptance targets.

The public [GitHub Wiki](https://github.com/mage-os-lab/module-shopping-feed/wiki) is a separate repository. A module release does not synchronize it. Composer dist archives exclude `docs/`, `dev/`, and contributor documents; use this repository or the wiki for those materials.
