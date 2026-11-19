# Logs and troubleshooting

Start with the feed-specific log and the feed grid status. They distinguish queue problems, product skips, generation failures, and upload failures.

> Documentation baseline: 1.3.0 release candidate. Last reviewed: 2026-11-12.

The original `9f07e46` category and promotion-date defects are repaired in 1.2.0, with runtime acceptance at `133af71`. Consult [Admin UI Component forms](Admin-UI-Component-Forms) for the exact deployed revision and acceptance evidence. Code updates do not reconstruct dates already erased by an earlier save.

## Log settings and location

Configure logging at **Stores > Configuration > Mage-OS > Mage-OS Shopping Feed > Log Settings**.

* **Logging Level** can be Debug, Info, Notice, Warning, or Error. Set it at Default Config scope; generation reads the default value even though the field is also displayed at website/store scope.
* **Log rotate (Kb)** defaults to 512. Before a run, a larger log is archived with a timestamp and unique suffix. Version 1.2.4 verifies the decompressed archive's size and SHA256 before truncating the active log; failure preserves the original.

Feed logs are restricted to `var/log` and use a safe name based on the feed ID, normally:

```text
var/log/mageos_shopping_feed_<feed_id>.log
```

Use **View Log** from the Feeds Management grid, or inspect the file as the Magento filesystem owner.

## Symptom guide

| Symptom | First checks |
| --- | --- |
| New category mapping causes `Undefined array key "id"` in candidate `9f07e46` | Upgrade to 1.2.0, which stores category IDs and recovers them from saved map keys before generation. Verify the mapped output after upgrading. |
| Promotion dates reopen blank in candidate `9f07e46` | Upgrade to the repaired date component before saving again. Recover previously erased dates from the pre-upgrade configuration; the code fix cannot infer them. |
| An unchanged editor save changes a legacy blank currency | Candidate `133af71` preserves the effective generation currency. Review earlier explicit selections against a backup; the fix does not silently reverse them. |
| Feed stock inclusion conflicts with storefront out-of-stock hiding | The stock correction in 1.2.0 makes feed-specific inclusion settings take precedence. Recompile after upgrading, then check simple and associated-product settings. |
| Frontend feature overrides appear ignored | The scope correction in 1.2.0 honors website/store overrides. Check the effective store value and clear the applicable caches. |
| Conditional Value shows its label but no condition controls after an update | Compare the published `js/form/parameter.js`, `template/form/parameter.html`, and conditional editor assets with the installed package. Back up/clear stale module Admin assets and rebuild each active theme/locale as described in [Installation and upgrade](Installation-and-Upgrade). Reload before testing again. |
| Feed stays Pending | Confirm Magento cron, the `mageos_shopping_feed` cron group, and the process command are running. |
| Direct command says generation failed | Check whether another process holds the per-feed lock, then open the feed log. |
| Feed reaches Error | Read the first exception or error for that run, not only the final status line. |
| No final file appears | Confirm the configured directory and filename, filesystem permissions, disk space, and whether a `.tmp` generation is still active. |
| Product is absent | Test the SKU, then check enablement, catalog visibility, stock settings, filters, category rules, complex-product configuration, and, since 1.2.5, the product's **Shopping Feed > Skip from Being Submitted** value for that store view. The feed log names products skipped by that field. |
| A child row is empty where the parent has the value | Add a **Value inheritance by column** row for that column under **Configurable Products** or **Grouped Products** and choose **associated if exists, otherwise from parent**. Save Columns Map first; the rule editor lists only saved columns. Bundle children have no inheritance rules. See [Complex products](Complex-Products#inherit-values-from-the-parent). |
| Row columns do not align | Review delimiter choice, mapped directives, source values containing delimiters or line breaks, and row counts across representative products. |
| Upload fails | Confirm mode, hostname, port, credentials, remote path, permissions, DNS, and firewall access from the Magento host. In 1.2.4, SFTP requires `setup:upgrade` and the trusted SHA256 server-key fingerprint. Confirm any changed key with the recipient before replacing it. |
| Test Feed is denied for a read-only role | Version 1.2.4 requires **Generate Feed** permission. The grid can still show its link; editor preview buttons are hidden without the permission. |
| Log rotation fails | Check free space and permissions in `var/log`. Version 1.2.4 preserves the active log until a complete gzip archive matches its size and SHA256; retain the source and failed archive for diagnosis. |
| `require is not defined` on a Hyva simple-product page | Confirm 1.0.0 or later is installed, the `hyva_` layout override is active, and stale theme overrides or full-page caches do not retain the Luma template. |
| Configurable link does not select a Hyva variant | Regenerate the feed and confirm its fragment includes numeric attribute IDs. Inspect selected options, price updates, and browser errors. |
| Available configurable variants appear out of stock | Confirm the parent is salable and 1.0.0 or later is installed. Check child stock, parent inheritance, and the selected website stock independently. |
| Local Inventory rows are missing | Confirm the website stock, source assignment, source-to-store mapping, salable quantity, and reservation state. |
| Google backorder/preorder is skipped | Map a valid future `availability_date` within one year. The log names the skipped row and required setting. |
| Google identifiers are blank | Map actual manufacturer identifiers. The Identifier Exists directive no longer infers absence from missing data. |
| Custom comma feed looks quoted or has unexpected splits | Use a CSV parser. Version 1.1 preserves embedded commas and doubles quotes; splitting on commas is not sufficient. |
| Standard-grid keyword search returns every feed | Run `setup:upgrade` and confirm the feed-name full-text index exists, then clear caches and retry. |
| Saved upload credentials fail to load | Replace unreadable credentials after checking schema upgrade status; previously truncated ciphertext cannot be recovered. |
| Google rejects data | Compare the exact generated value with the current Google specification and account diagnostics. |

## Locks and interrupted runs

Generation uses `flock` on `var/tmp/mageos_shopping_feed_<feed_id>.lock`. An empty lock file can remain after a successful run, but the operating-system lock is released. Do not treat the file's existence as proof of a stuck process.

Before intervening, confirm whether a worker is active and whether the file or log is still changing. If no process holds the lock, a new run can acquire it normally.

## Temporary files

A `.tmp` feed is an in-progress artifact. The module moves it to the final filename only after the full generation completes. Diagnose the failed run before removing artifacts, especially when another worker may still be active.

## Escalation evidence

Capture the minimum useful evidence before reporting a defect:

* Module version and exact commit
* Mage-OS or Magento Open Source version and PHP version
* Feed type, feed ID, store view, and relevant settings
* Exact command or Admin action
* First relevant error and surrounding log lines with secrets removed
* Test SKU and expected versus actual field values
* Whether the problem reproduces without upload destinations

For slow or incomplete large feeds, continue with [Performance and large catalogs](Performance-and-Large-Catalogs).

## Conditional rules (1.3.0)

For rejected saves or generation errors, review the selected attributes, valid option IDs, operator types, date format, and both Conditional Value outputs. A nonnumeric percentage source is an error; it does not select Otherwise. See [Conditional values](Conditional-Values#troubleshooting-and-verification).

Product Selection skip counts describe product/parent candidates, not distinct SKUs or expanded inventory rows. Rules do not override existing exclusions. Review [Product selection](Product-Selection#logs-performance-and-recovery) for counts, invalid-rule recovery, and the distinction between active-parent and standalone-parent evaluation.

## XML output (1.3.0)

If a Generic XML feed will not save, check element names, declared prefixes, matching header/footer elements, and whitespace before the XML declaration. DTDs and entity declarations are not supported.

Invalid product text or malformed manually mapped compound values skip the affected row; inspect the source SKU, mapped row ID, and column in Test Feed or View Log. A parent SKU can be the source for a composite row. Fix the data or mapping and regenerate. Configuration or file-write errors stop generation and preserve the prior published file. Inspect its timestamp rather than assuming an existing download is the new run. See [XML Output](XML-Output#validation-and-failed-rows).
