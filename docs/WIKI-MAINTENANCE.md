# Wiki maintenance

The Markdown under `docs/wiki` is the reviewable source for the public GitHub Wiki. GitHub stores the wiki in a separate `module-shopping-feed.wiki.git` repository. There is no automatic synchronization workflow in this project: a module commit, merge, tag, or release does not update the wiki. Do not edit the public wiki and this directory independently.

## Source order

Resolve documentation claims in this order:

1. Behavior observed on the exact module commit in a representative Mage-OS or Magento Open Source runtime
2. Current module code, configuration, tests, and acceptance plan
3. Current official Magento, Mage-OS, Google, and GitHub documentation
4. Historical Rocket Web documentation as migration source material only

## Page convention

Every user-facing page should contain:

* One clear purpose
* A documentation baseline and review date. Use the released runtime tag for code behavior and preserve the original scope of older acceptance results; the documentation commit itself is recorded separately at publication
* Prerequisites or safety boundaries where relevant
* Exact Admin paths, commands, and setting names
* A verification step
* Links to related pages instead of duplicated procedures

Use `_Sidebar.md` for navigation and `_Footer.md` for repository links.

Use `docs/WIKI-CONTENT-MAP.md` to confirm that historical subjects have a current destination or an explicit reason for exclusion.

## Screenshots

Add a screenshot only when it clarifies a stable module-owned interface. Capture it from the exact release candidate, crop it to the relevant control, add useful alt text, and remove store names, domains, credentials, customer data, product data, and internal paths.

Prefer text for third-party interfaces such as Google Merchant Center because their navigation changes independently of this module. Store image source files alongside the reviewable documentation rather than only in the public wiki checkout.

## Publication workflow

1. Update the pages in `docs/wiki` on a review branch.
2. Run `php dev/tests/validate-wiki.php`.
3. Review the rendered Markdown and any new screenshots.
4. Confirm the baseline commit named on every page resolves in the public repository and matches the reviewed source tree.
5. Merge the reviewed source change into the module repository. Release-state copy can be prepared in the release PR, but publish it to the wiki only after the corresponding release is available.
6. If the Wiki feature is unavailable, obtain approval to enable it in repository settings.
7. Initialize the GitHub Wiki with `Home.md` if it does not exist.
8. Clone `https://github.com/mage-os-lab/module-shopping-feed.wiki.git`.
9. Copy the reviewed page set into the wiki checkout.
10. Review the wiki diff and commit it separately.
11. Obtain approval for the exact wiki commit and destination.
12. Push the wiki default branch, then verify the live Home page, sidebar, footer, links, and images.

Publishing wiki content does not imply permission to commit or push changes to the module repository, and a module push does not imply permission to publish the wiki.

## Drift checks

Review the wiki for every release that changes:

* Composer or platform requirements
* Admin paths, sections, fields, or ACL resources
* Feed types, directives, or default columns
* Output files, logs, locks, commands, or cron behavior
* Upload modes or credential handling
* Google data specifications or storefront event formats
* Migration behavior

Before publication, scan all operating guides and the README, migration/developer guides, acceptance plan, sidebar, and footer for stale install constraints, permissions, workflow names, release status, branch-only wording, and deployment claims. Keep old workflow links only when they identify historical runs. Reconcile repository links and anchors as well as wiki navigation. Verify the published tag, release, CI SHA, and Composer metadata independently.

At minimum, recheck Google-owned links and terminology at release time. Keep Merchant Center navigation brief because its interface changes independently of this module.

## 1.2.5 documentation baseline

Release-focused pages, the installation checklist, and the columns, filters, and troubleshooting guides name `v1.2.5` as their baseline. The new release page and upgrade checklist cover the created **Skip from Being Submitted** attribute and store-view option labels; both need only `setup:upgrade`. Unchanged guides keep the 1.2.4 baseline until the post-publication documentation refresh, which also records the release commit, CI run, and Packagist state, repins README screenshots, and updates the security policy and documentation index. Public wiki publication follows the workflow above once the release exists; the [release preparation record](reviews/2026-10-22-release-1.2.5-preparation.md) lists the exact steps.

## 1.2.4 documentation baseline

All current operating guides were reviewed against `v1.2.4` on October 20, 2026. Historical release pages and dated acceptance reports retain their original versions and test scope. Release 1.2.4 is published at `75dd335f930aef78ae693f62c3039e469c2d26f6`; its 37 post-merge CI jobs pass. The release page and upgrade checklist cover the required SFTP fingerprint, schema update, encrypted upload model data, and Test Feed permission. The October 20 security record covers the local Mage-OS 3.6 acceptance. The [documentation review](reviews/2026-10-20-documentation-review.md) records source/public-wiki drift and remaining limitations. Public wiki publication remains a separate action under the workflow above.

## 1.2.3 historical documentation baseline

The previous source set named `v1.2.3` as its baseline. Release notes and upgrade requirements are in `docs/releases/1.2.3.md` and `Release-1-2-3`; historical release pages retain their versions. Native inventory, promotion, manual batching, Hyvä, and Nebula grid/fallback results are in the October 8 reports. The October 13 release preparation record combines those changes with the package-version display fix from main. That source set was published separately to the wiki; it does not include the later 1.2.4 release changes.

## 1.2.0 historical documentation baseline

Release 1.2.0 includes the UI Component editor and five catalog presets. Release notes live at `docs/releases/1.2.0.md`; the historical preparation record and publication checklist live at `docs/reviews/2026-10-03-release-1.2.0-preparation.md`. The original `9f07e46` browser failures are repaired; the accepted local runtime is `133af71`. Current evidence starts with `docs/reviews/2026-10-03-local-acceptance.md`, followed by the dated form, permission, preview, and operational reports it links. Keep released 1.1 instructions identifiable. Preserve historical results and add a prominent link when later evidence changes their disposition.

When preparing publication, update every linked candidate notice, the README, changelog, developer guide, and acceptance record together. Verify that the referenced commit and evidence are publicly available before copying pages into the wiki. Local documentation edits and local deployment do not publish the wiki or establish external recipient acceptance.

## 1.3.0 candidate publication

The release branch includes XML, Conditional Value, Product Selection and modifiers, plus `Release-1-3-0` and the cumulative upgrade guide. The checked-in pages describe a candidate until the package is published. After approval, merge and verify the final CI, publish `v1.3.0`, confirm Packagist, then replace candidate notices with the actual release date and evidence. Synchronize the public wiki using the procedure above. Do not treat the branch push or PR as a package or wiki release.
