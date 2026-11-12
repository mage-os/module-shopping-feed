# SHO-5: fixes following the independent retest

Date: 2026-11-12. Prepared locally on `feature/sho-5-per-column-modifiers`, before integration into the 1.3.0 release branch. The [independent retest](2026-11-11-sho-5-independent-retest.md) remains the historical record for its tested source snapshot.

## Changes

- **A, mixed delimiters:** Escape HTML now requires enclosure whenever any delimiter character is an ASCII letter, digit, ampersand, hash, or semicolon. Separators such as semicolon-space, `;|`, and `|&` can overlap an entity boundary and are rejected at save and generation. Tab, pipe and automatically enclosed Generic CSV retain their behavior.
- **B, effective Google columns:** Save validation uses the same map normalization as generation without consulting a stale map cache. Legacy `promotions_id` and canonical `promotion_id` target one chain, preserving their interleaved row order. Runtime-added variant columns such as `color` are accepted.
- **C, text boundaries:** Strip tags shares the default markup remover, preserving spaces across paragraphs, line breaks, list items and comments. Script/style bodies are discarded, while existing entities remain encoded. The default cleaning path retains its existing behavior.
- **D, keyboard ordering:** Modifier rows expose their native Order field. Editing its number changes the sequence through Magento DynamicRows, and the existing save decoder sorts by position and removes UI bookkeeping. Drag ordering remains enabled.

## Verification

Raw evidence and scripts: `/private/tmp/sho5-r2-fixes-20261112/`.

- The independent nine-case regression suite failed 9/9 before the fixes and passed afterward. Additional tests first reproduced mixed-delimiter rejection failures, the canonical Google alias and runtime color failures, and the hidden Order field.
- Both complete unit suites pass: PHPUnit 12 on PHP 8.4 and PHPUnit 9 on PHP 8.3, 1,125 tests each. Focused Clover coverage confirms execution of normalization, name resolution, markup removal, modifier compilation/application, and form decoding.
- Full PHPStan level 8 passes from an isolated export with the required vendor dependency tree. Two obsolete baseline entries were removed; no suppressions were added. Full PHPCS passes apart from upstream deprecated-sniff notices.
- JavaScript: 79 frontend/CI tests and 8 installed Magento UI framework tests pass. Wiki, Magento XML and baseline validators pass; the baseline shrank from 1,714 entries / 2,403 errors at `origin/main` to 1,711 / 2,398.
- Installed Mage-OS 4.0.0, PHP 8.4, guarded disposable database `sho5r_20261111`: 20/20 checks pass. Five mixed delimiters are rejected with stored configuration unchanged. Real generated cells preserve markup word boundaries and entities. A Google file applies interleaved legacy/canonical modifiers and modifies runtime-added color. Runtime rejection preserves the previously generated file.
- Admin browser: the new fixture's Order field is visible and keyboard editable. Changing the uppercase row from 1 to 20 moves it after append; save/reload persists `append, uppercase`, and generation changes `MIXEDxyz` to `MIXEDXYZ`. UI position metadata is not persisted. An initial browser attempt ran before the section was ready; its unchanged output was discarded and the test repeated with confirmed input focus and values.

## Scope and retained evidence

Only new disposable feeds 220 and 221 were created in the retest database; no existing catalog fixtures were changed, no schedules were enabled and no uploads were attempted. The installed module files were backed up before testing and restored afterward. The independent snapshot and evidence remain intact.

The independent report's seven-preset, cron, full-catalog, batch and pre-feature checks describe its earlier snapshot. They are not presented as reruns of these fixes. XML/modifier interaction requires verification on the combined 1.3.0 branch. External recipient ingestion remains outside this local verification.
