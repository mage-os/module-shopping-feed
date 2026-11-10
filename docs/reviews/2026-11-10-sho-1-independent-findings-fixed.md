# SHO-1 independent findings: fixes and retest

Tested 2026-11-10 on `feature/SHO-1-xml-output` in `/private/tmp/shopping-feed-sho-1`. HEAD remains `4aa30bc3d3c8aa70e0647946ef116151591fab5c`; these changes and the earlier preset work are uncommitted. Nothing was pushed, merged, deployed remotely, or submitted to a provider.

The confirmed XML defects are fixed and locally verified. Provider ingestion remains unverified. The shared SHO-2/SHO-3 source checkout and store on port 8131 were not changed.

## Finding disposition

| Finding | Result |
| --- | --- |
| F1: one invalid character fails the entire feed | Fixed. Invalid XML product text and malformed manually mapped compound values raise a distinct row-data exception. Generation skips that row, increments the skipped count, and logs the mapped ID, source SKU, and column. Later rows continue. Invalid configuration and I/O errors still stop generation. Admin Test Feed displays the actionable diagnostic in Log Output. |
| F2: punctuation in shipping titles loses field boundaries | Fixed. The shipping mapper retains structured groups through XML rendering. Country, region, service, and price are never reconstructed by splitting built-in shipping titles. Versioned persistent XML cache values preserve the groups; old flattened cache values are recomputed, and switching to delimited recomputes incompatible XML entries. Delimited bytes are unchanged. |
| F3: wrapper fields render as single-line inputs | Fixed. A dependent component extends Magento's native textarea, including its required defaults. Header and footer retain newlines through browser edits, saves, and generation. |
| F4: Meta/Pinterest sample mismatch | Fixed the confirmed namespace discrepancies. Meta additional images are unprefixed; Pinterest title, description, link, and sale price are unprefixed. Namespace exceptions are destination configuration. All other existing preset field mappings remain. |
| F4: Local Inventory pickup fields | Added configured nested `pickup_cost` serialization and documentation for optional merchant mappings. The module cannot infer pickup eligibility, service level, or fees. The reported fee requirement is conditional, not universal. |
| L1/L3: invalid names/wrappers save successfully | Fixed. Saving an XML feed validates the same writer contract used by generation, including optional column namespaces. Bad settings are rejected before persistence. |
| L2: valid Unicode names rejected | Fixed. Qualified names use XML 1.0 NameStartChar/NameChar ranges. Unicode names work in saved wrappers and mapped fields. |
| L4: delimited-only fields stay visible | Fixed. Delimiter, Other Delimiter, enclosure, and Empty Cell Value follow the selected format. Other Delimiter also follows the delimiter selection. |
| L5: stray XML option on OpenAI | Fixed. Output Format uses Magento's native bounded select rather than preserving unknown options. An existing invalid XML selection displays only Delimited; saving XML directly is rejected. |
| L6: commas inside additional image URLs | Fixed for the built-in mapper. Typed list values retain each URL intact until serialization, including a comma inside a CDN path. Manually supplied string lists still use comma separators. |
| L7: obsolete translation notices | Removed superseded CSV/TSV-only setup notices. |

For composite products, a diagnostic identifies the source adapter SKU and mapped row ID. The source SKU can be the parent; the mapped ID helps locate the specific child row.

## Verification

Runtime: isolated Mage-OS 4 / Magento 2.4.9, PHP 8.4.27, `/private/tmp/sho1i-20261110/app`, database `sho1i_20261110`, Admin on `127.0.0.1:8141`. No provider accounts were mutated.

- **PHPUnit: 997 tests, 2,799 assertions, passing.** New regressions cover row skipping versus configuration failures, Unicode names, save validation, shipping punctuation/cache transitions, image URLs with commas, provider namespaces, and pickup costs. Negative controls against the pre-fix source failed for the expected defects. Clover records execution of the actual writer, generator, feed validation, shipping/image mapper, and form metadata methods.
- **Node: 86 tests passing.** Includes the installed Magento textarea contract and dependent visibility, as well as the existing frontend and CI suites.
- **PHPStan: no errors.** Removed one obsolete baseline entry after correcting the shipping return type; no baseline errors were added.
- **PHPCS: no code errors or warnings.** The installed tool emits its existing deprecated-sniff notices.
- **Repository validators:** module validation, wiki validation, Magento XML validation, and `git diff --check` pass.
- **Installed invalid-data/save checks: 19 passing**, observed failing before installation. U+000B, U+000C, U+001F, and U+FFFE each skip exactly one row. Cron publishes a complete document and removes the temporary file; preview returns without an uncaught exception. Logs name the SKU and column. Invalid names, mismatched wrappers, whitespace before the XML declaration, DTDs, and unsupported OpenAI XML fail at save time. Unicode names and multiline wrappers generate successfully.
- **Wrapper matrix: 31/31 passing.** Includes DTD/XXE, entity expansion, undeclared entities and prefixes, hidden items inside comments/CDATA/PIs, encoding/version mismatch, malformed tags, Unicode names, and empty-item fallback. Rejected saves preserve the previous published file.
- **Shipping punctuation:** installed Google XML preserves `Ground: 3-5 days, tracked - Fixed` as one `g:service`. Its delimited twin retains its previous representation.
- **Six preset comparison: 75/76 passing.** The sole mismatch is independent report P1: repeated same-name columns duplicate delimited cells on the pre-SHO-1 baseline. The comparison's previous assumption that every field has the Google namespace was updated to the verified provider samples. No new mismatches remain.
- **Preview isolation: 9/9 passing.** Full runs and counts remain independent of preceding SKU previews.
- **Cron batches:** generic limit 2, Google limit 3, empty feed, exact multiple, and a bad product in the final batch all produce one header/footer, valid XML, no duplicate rows, no leftover temporary file, and the same items as an unbatched reference. The bad-product case finishes Completed with six valid items rather than retrying the whole feed.
- **Delimited regression:** 12 newly created baseline feeds, including all six destinations and six generic delimiter/encoding/default-value variants, produce byte-identical files and previews on the baseline and fixed implementations.
- **Pickup:** installed Local Inventory generation produces both nested pickup-cost subfields with mapped values.
- **Real Admin:** both wrapper controls are textareas; a three-line header edited to `version="3"` persists with its newlines and generates valid XML. XML/delimited toggling hides/shows the expected controls. Test Feed shows the bad SKU and `description` error. A historical OpenAI XML configuration offers only Delimited. No console errors remain after the corrected form is loaded.

Commands from the SHO-1 worktree:

```sh
export MAGENTO_ROOT=/private/tmp/sho1i-20261110/app
/opt/homebrew/opt/php@8.4/bin/php -d xdebug.mode=coverage \
  "$MAGENTO_ROOT/vendor/bin/phpunit" -c phpunit.xml.dist \
  --coverage-filter Model --coverage-filter Ui \
  --coverage-clover /private/tmp/sho1-fixes-20261110/coverage.xml
node --test dev/tests/frontend/*.test.cjs dev/tests/ci/*.test.cjs dev/tests/magento-ui-form.test.cjs
php dev/tests/validate.php
php dev/tests/validate-wiki.php
php dev/tests/validate-magento-xml.php
php dev/tests/check-phpstan-baseline.php --git-ref=HEAD phpstan-baseline.neon
git diff --check
```

PHPCS and PHPStan used the existing standards checkout's dependencies through a temporary `vendor` symlink, removed after verification. Both ran the SHO-1 worktree's configuration and source.

Functional scripts remain in `/private/tmp/sho1i-20261110`: `retest-fixes.php`, `wrappers-retest.php`, `presets-retest.php`, `pickup-retest.php`, and the independent review's shipping, isolation, batching, and delimited regression scripts. Logs, negative controls, coverage, and screenshots are in `/private/tmp/sho1-fixes-20261110`. Temporary product-description and carrier-title changes were restored. Synthetic feeds remain available for inspection.

## Remaining boundaries

- No provider has ingested these files. TikTok's `g:id` alias and packed image convention remain unconfirmed against an account ingestion endpoint. The guide states this explicitly.
- Google does not receive tax data from the default mapping. Existing optional custom tax mappings remain supported for compatibility; this work does not claim Google still uses them.
- Pickup cost is required only under Google's stated country, pickup, and fee conditions. Merchants must map actual service levels and costs. Defaults do not fabricate them.
- Microsoft filename/account/market requirements still require merchant review. A provider-specific filename length gate was not added to the general output-path validator.
- Independent report P1 and P3-P7 remain pre-existing issues. F1 removes invalid XML product text as a trigger for the whole-feed retry loop; it does not redesign queue retries or the generic handling of unrelated preview failures.
- SHO-2/SHO-3 integration, provider ingestion, large-catalog performance, production-mode static deployment, and broader store/ACL variations were not run in this pass.

## Primary provider references checked

- [Meta catalog reference](https://developers.facebook.com/docs/marketing-api/catalog/reference/), including its unprefixed additional-image example.
- [Pinterest catalog guide](https://help.pinterest.com/en/business/article/before-you-get-started-with-catalogs) and its [downloadable XML sample](https://s.pinimg.com/sub/helpcenter/assets/pinterest_product_sample_xml_feed.xml.zip).
- [Google Local Inventory specification](https://support.google.com/merchants/answer/14819809?hl=en-GB) and [pickup cost](https://support.google.com/merchants/answer/16988704?hl=en).
- [Google Merchant Products API release notes](https://developers.google.com/merchant/api/release-notes/rest/products?hl=en) for tax deprecation context. This does not establish ingestion rejection of a legacy feed attribute.
