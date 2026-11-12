# Release acceptance

Release acceptance proves the extension on representative Magento runtimes and proves each generated artifact at the boundary where it is consumed.

> Documentation baseline: release 1.2.5 (`v1.2.5`); earlier acceptance is identified by version. Last reviewed: 2026-10-22.

Magento Open Source 2.4.7-p10 has a separate compatibility profile and upstream Flysystem advisory. Review [Status and compatibility](Status-and-Compatibility) before installing on that version; the tested optional backport is not applied automatically.

## 1.3.0 candidate

The [release preparation record](https://github.com/mage-os/module-shopping-feed/blob/release/1.3.0/docs/reviews/2026-11-12-release-1.3.0-preparation.md) records the combined candidate checks. XML/modifier integration is tested separately from the earlier feature reports. Remote CI, merge, tagging, Packagist indexing, wiki publication and external recipient acceptance remain distinct states.

## 1.2.5 verification

The [product-attribute verification record](https://github.com/mage-os-lab/module-shopping-feed/blob/v1.2.5/docs/reviews/2026-10-22-pr-24-verification.md) covers 951 unit tests on PHP 8.4 and 8.5, 38 integration tests on a fresh Mage-OS 3.6.0 install, four database persistence tests, 84 JavaScript checks, PHPStan level 8, the Magento coding standard, and the Composer, wiki, and consolidation validators. Integration scenarios cover store-view exclusion on configurable children and the parent, core non-table option sources, attribute sets created after the upgrade, and patch re-application. The [functional record](https://github.com/mage-os-lab/module-shopping-feed/blob/v1.2.5/docs/reviews/2026-10-21-product-attributes-issues-22-23.md) covers real `setup:upgrade` and its repeat, the Admin product form with store-view overrides, Admin and CLI previews, and queued generation on an upgraded store copy.

External recipient ingestion, native Nebula acceptance, other target-store behavior, and production volume were not repeated. The "rows skipped" counter does not include products excluded by the new field, and the preset output-limit key mismatch remains a known issue.

## 1.2.4 verification

The [Mage-OS 3.6 verification record](https://github.com/mage-os-lab/module-shopping-feed/blob/v1.2.4/docs/reviews/2026-10-20-standards-security-mageos36.md) covers 939 unit tests, 30 integration tests, four database persistence tests, 84 JavaScript checks, DI compilation, and standard Admin save/reopen. Real local FTP/SFTP transfers verify plain and gzip payloads, fingerprint rejection before authentication, temporary-file cleanup, and complete archived logs. Static checks and dependency audits pass for the recorded local candidate. All 37 [post-merge CI jobs](https://github.com/mage-os-lab/module-shopping-feed/actions/runs/39372608509) passed for tagged commit `75dd335f930aef78ae693f62c3039e469c2d26f6`, including the exact-version legacy advisory policy. [Release 1.2.4](Release-1-2-4) links exact-commit CI and upgrade requirements.

External recipient ingestion, native Nebula acceptance, other target-store behavior, and production volume were not repeated in this local run. The preset output-limit key mismatch remains a known issue.

## 1.2.3 verification

The [hardening report](https://github.com/mage-os-lab/module-shopping-feed/blob/v1.2.3/docs/reviews/2026-10-08-hardening-and-mageos-3.5-verification.md) records native MSI, all eight presets, promotions, manual queue recovery, malformed Admin identities, and Luma/storefront checks. The [native-theme follow-up](https://github.com/mage-os-lab/module-shopping-feed/blob/v1.2.3/docs/reviews/2026-10-08-remote-ci-and-native-theme-acceptance.md) records Hyvä 1.5.2 and Nebula 0.9.0 listing, actions, and supported editor fallback. The combined release candidate adds the package-version display correction already on main and passes 922 local unit tests with 2481 assertions. [Release 1.2.3](Release-1-2-3) links the release record and exact-commit CI verification.

The October 8 browser run uses the supported Magento backend fallback for Nebula editors, previews, and logs. Native UI Bridge rendering, strict Hyvä CSP themes, external provider ingestion, and production-catalog capacity remain separate checks. Historical scheduling, transport, volume, and rollback results below retain their original revisions.

## 1.2.2 verification

The local core suite passes 863 tests on Mage-OS 3.5/PHPUnit 12, Magento 2.4.8/PHPUnit 10, and Magento 2.4.7-p10/PHPUnit 9; 47 JavaScript checks pass. Seventeen new description cases include the exact #14 report. Nine fail against the earlier runtime and pass with the correction. The merged runtime passed all 25 [post-merge CI jobs](https://github.com/mage-os-lab/module-shopping-feed/actions/runs/37332361658). See [Release 1.2.2](Release-1-2-2) and the repository's `docs/reviews/2026-10-05-release-1.2.2-preparation.md` for exact baselines and publication boundaries.

Users migrating from Rocket Shopping Feeds can install the optional [Rocket Web migration companion](Rocket-Web-Migration). Existing-store migration, recipient acceptance, and explicit activation remain separate checks.

## 1.2.1 verification

Version 1.2.1 passes 846 local unit tests on both native frameworks and 47 JavaScript checks. The 32 new regression cases reproduce 22 assertion failures in v1.2.0 and pass with the fixes. The companion passes 63 unit/database tests per framework and 24 opt-in legacy runtime tests. Full store/browser/generated-output acceptance was not all repeated against 1.2.1; earlier results below retain their 1.2.0 scope. See [Release 1.2.1](Release-1-2-1).

## 1.2.0 acceptance record

The historical 1.2.0 runtime `133af71` was verified on `mageos-latest` and the Magento Open Source 2.4.8/2.4.9 Docker profiles. The original category-generation and promotion-date failures are repaired. Form persistence/output, permissions, previews, stock selection, native configurable MSI, pricing, frontend scope, 5,000-product generation, private receiver delivery, and rollback have recorded local evidence. See [Admin UI Component forms](Admin-UI-Component-Forms) and the repository's `docs/reviews/2026-10-03-local-acceptance.md` for the hourly soak, cleanup state, exact scope, and linked historical results.

The [1.2.0 release summary](Release-1-2-0) links the release notes and upgrade checklist. Release publication does not establish external provider ingestion, a customer's transfer destination, native Nebula UI Bridge behavior, consent-managed tag delivery, Varnish behavior, or production-catalog capacity. Test the exact release on each intended target and preserve those boundaries in the release record.

The repository's [`ACCEPTANCE-TEST-PLAN.md`](https://github.com/mage-os-lab/module-shopping-feed/blob/main/ACCEPTANCE-TEST-PLAN.md) is the detailed test source. This page describes the evidence expected from a release candidate.

For historical 1.1 results and limits, see [Release 1.1.0](Release-1-1-0). The earlier [1.0.0 record](Release-1-0-0) remains historical evidence. The general profiles below remain the acceptance guide for each target deployment; the release record does not claim every external integration was exercised.

## Required profiles

Test the release candidate against the supported Mage-OS and Magento Open Source profiles declared by Composer and CI. Include a store with cron, writable media and log directories, storefront product pages, and representative simple and complex products.

Features that need additional fixtures should be tested with them:

* Standard Admin with Nebula absent, plus Nebula Admin when supported
* Upgrade from the prior patch, including existing SFTP destinations without fingerprints, plus older schema/editor transitions when applicable
* Generate Feed permission for Test Feed, including denial of read-only roles
* Log-archive equality before truncation and preservation on archive failure
* Google identifier/date edge cases and CSV parser round trips
* MSI sources, website stocks, and reservations for Local Inventory
* Active cart price rules for Promotions
* Configurable products with swatch and select attributes for deep links
* An existing consent-managed Google tag integration for direct `view_item` delivery
* A quarantine FTP or SFTP target for transfer tests

## Acceptance layers

| Layer | Evidence |
| --- | --- |
| Installation | Module enables, setup completes, Admin route and ACL work, no legacy package is required |
| Configuration | Every feed section survives save, reload, and an unchanged save; generated output confirms the saved values |
| Product data | Expected headers, stable row shape, sampled values, price behavior, and complex-product output |
| Operations | Manual run, queue, cron, batch continuation, locking, atomic final file, logging, and retries |
| Integrations | Local Inventory, Promotions, schema.org, deep links, `view_item`, and uploads where enabled |
| Compatibility | CI and runtime evidence for the supported platform matrix |
| Upgrade | Clean install and upgrade from the previous Mage-OS module release |

## Safety rules

* Use test products, stores, rules, credentials, and destinations.
* Do not point an unaccepted feed at a serving endpoint.
* Do not enable live ad event delivery without the site's consent and tag owners.
* Do not infer Google acceptance from a locally valid-looking file. Capture the current account diagnostic or API result.
* Do not infer production readiness from unit tests or CI alone.

## Release record

Record the exact commit and package artifact, environment versions, validation output, generated file checksums, sampled expected-versus-actual data, external diagnostics, unresolved limitations, and cleanup result.

Track prepared, locally verified, committed, pushed, CI green, released, installed, externally accepted, and live-verified as separate states. Approval for one state does not imply the next.
