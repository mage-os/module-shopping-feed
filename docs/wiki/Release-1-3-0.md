# Release 1.3.0

> Documentation baseline: 1.3.0 release candidate, not yet published. Last reviewed: 2026-11-12.

Version 1.3.0 combines [XML output](XML-Output), [Conditional Value](Conditional-Values), [Product Selection](Product-Selection), and [per-column modifiers](Columns-and-Directives#per-column-modifiers-130). Existing feeds retain their default delimited format; the new transformations and selection rules are opt-in.

Full [release notes](https://github.com/mage-os/module-shopping-feed/blob/release/1.3.0/docs/releases/1.3.0.md) describe the feature set, corrections, upgrade and rollback requirements. Follow the [1.3.0 upgrade checklist](Installation-and-Upgrade#upgrading-to-130) before enabling schedules or uploads.

The candidate also documents parent-value inheritance, fixes conditional editor initialization, preserves XML shipping/image boundaries and invalid-row diagnostics, and supports keyboard modifier ordering. XML modifier processing uses UTF-8 and preserves repeated/nested values.

The [preparation record](https://github.com/mage-os/module-shopping-feed/blob/release/1.3.0/docs/reviews/2026-11-12-release-1.3.0-preparation.md) separates local verification from CI, publication, external recipient ingestion and target-store acceptance. No stable Composer package is available from this branch until the release is tagged and indexed. Public wiki synchronization is separate.
