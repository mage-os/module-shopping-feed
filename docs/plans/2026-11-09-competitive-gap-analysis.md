# Competitive gap analysis: Amasty Product Feed and Mirasvit Advanced Product Feeds

Prepared November 9, 2026 against `main` at `dc72fb15253d5d7b7f9d8a2c3eda29d6a2dbbb98` (release 1.2.5).

A user reported that the module lacks the "ability to use a parent attribute for children conditionally (if empty)" and called it critical for configurable products. This document checks that report against the code and compares the module with the two paid extensions most often named alongside it: [Amasty Product Feed](https://amasty.com/product-feed-for-magento-2.html) (version 5.4.0, $249 plus paid add-on packages) and [Mirasvit Advanced Product Feeds](https://mirasvit.com/magento-2-extensions/advanced-product-feeds.html) ($179 first year).

Competitor capabilities come from the vendors' product pages and the Amasty user guide as read on November 9, 2026, not from installed copies. Several Amasty features are gated behind "an active product subscription or support subscription" and are noted as such. Module capabilities come from the code, README and wiki at the revision above. Nothing here was verified in a running store.

## The reported gap already exists

The feature is **Value inheritance by column**, in the feed editor under **Configurable Products** and **Grouped Products**. Each row names a column and a mode:

| Mode | Behavior |
| --- | --- |
| associated if exists, otherwise from parent | Child value, falling back to the parent when the child value is empty. This is the requested behavior. |
| parent if exists, otherwise from associated | Parent value, falling back to the child. |
| parent only | Always the parent value. |
| associated only | Always the child value (the default without a rule). |

The rule applies to any column, including directive columns such as the image URL. For the Concatenate directive, the optional **Only for attributes** field lists the attribute codes inside the pattern that follow the rule. The implementation is `Composite::mapByInheritance()` and `Composite::mapByInheritanceFrom()` in [Model/Product/Adapter/Type/Composite.php](../../Model/Product/Adapter/Type/Composite.php); the configurable and grouped adapters read `configurable_map_inherit` and `grouped_map_inherit`. Unit tests cover both adapters.

Three limits explain the report:

1. **Discoverability.** The control sits in the product-type sections, not in Columns Map where merchants look for per-column behavior. The README has one table row on it and the [Complex products](../wiki/Complex-Products.md) page names "per-column inheritance" without a how-to. Amasty exposes the same idea as a **Parent** option on every content row (Yes, Yes if empty, Yes strict), which is where users expect it.
2. **Save first.** The column dropdown lists only columns present in the saved Columns Map, so a new feed shows nothing to choose until it has been saved once.
3. **No bundle support.** `Bundle` does not override `getAssociatedMapInheritance()`, so bundle children cannot inherit. Amasty also has a **Parent Data Priority** setting for a simple product that belongs to both a configurable and a bundle; the module has no equivalent.

Recommended actions, all small:

- Add a how-to section to the Complex products wiki page and a troubleshooting entry ("A child row is empty where the parent has a value").
- Offer the inheritance mode on each Columns Map row, writing to the same `*_map_inherit` config, so the feature is visible where the column is defined.
- Read an inheritance map in the bundle adapter and add the section to the editor metadata.
- Consider a feed-level default ("Children inherit empty values from their parent: Yes/No") so merchants do not have to add a row per column.

## Feature comparison

| Area | Mage-OS Shopping Feed 1.2.5 | Amasty Product Feed 5.4 | Mirasvit Advanced Product Feeds |
| --- | --- | --- | --- |
| Output formats | Delimited only (tab, comma, other; enclosure controls). The `.xml` extension is allowed but does not change the serializer. | XML with header, item wrapper, footer, optional tags and CDATA control; CSV; TXT. | CSV, TXT, XML, JSON Lines, Apache Parquet. |
| Channel presets | Generic, Google Shopping, Google Local Inventory, Google Promotions, Meta, Microsoft, TikTok, Pinterest, OpenAI (beta). | Custom, Shopping, Bing, Google, Amazon (four feeds), eBay, Instagram, TikTok, Pinterest, Facebook, ChatGPT, Rakuten, Idealo. Most non-Google templates are a paid add-on. | 130+ templates including Google variants, Meta, TikTok, Pinterest, Criteo, Klaviyo, ChatGPT, Amazon-adjacent marketplaces and most European comparison engines. |
| Parent/child values | Per-column inheritance with four modes for configurable and grouped; three row models per complex type; parent stock inheritance; variant deep links. | Per-row Parent option (Yes, Yes if empty, Yes strict); Parent Data Priority; exclude children when the parent is disabled or has images. | Parent attribute references in patterns; filters on parent attributes; configurable and grouped prices from children; bundle prices from selections. |
| Column transformations | Replace empty values (ordered fallback chain), Find and Replace, Limit column output, HTML cleanup, Concatenate Attributes pattern, Static Value, price with tax parameter. | Modifier chain per row: strip tags, HTML escape, Google HTML escape, remove widget HTML, case changes, integer, length, prepend, append, replace, round, if empty, if not empty, secure URL; date and price formats; decimal places; currency abbreviation. | Pattern shortcodes with filters, basic PHP functions, calculations and image resize; dynamic variables in PHP. |
| Condition-based values | Google-only price buckets. | Condition-Based Attributes: rule conditions choose an output attribute or text, with percentage or fixed modification and a default; Merged Attributes. | Dynamic attributes from conditions; dynamic variables. |
| Product selection | Out of stock, product types, attribute sets, category include/exclude, Google-only price bounds, skip rows with empty columns, per-product skip attribute. | Conditions tab using Magento rule conditions on any attribute; toggles for disabled, out of stock, not visible, without images, children of disabled parents. | Price, stock (salable), categories, SKU, image presence, any attribute, parent attributes; extensible conditions. |
| Category mapping | Per feed: include/exclude, priority, taxonomy and product type per Magento category; Google taxonomy download per locale. | Reusable Categories Mapping entities shared across feeds; autocomplete against Google taxonomy. | Dynamic categories; AI-assisted Google category mapping. |
| Scheduling | Daily hour rows with batch mode; hourly queue job plus a per-minute worker. | Days of week and time. | Fixed days and times or a five-field cron expression with preview. |
| Delivery | FTP, SFTP with pinned SHA256 host key, gzip. No test button. | FTP/SFTP with passive mode and Test connection; zip, gz, bz compression. | FTP, FTPS, SFTP with Test Connection. |
| Google Merchant API | None. | Merchant API submission after each generation, data source created automatically; paid add-on. | Merchant API submission for products, supplemental, local and regional inventory, promotions and reviews. |
| Preview and testing | Test Feed for one SKU or product ID. | Preview Feed for a configurable number of products. | Pattern preview; validation rules with line-numbered errors; CLI validation. |
| History and notifications | Per-feed log file with rotation and progress lines. | Generation History grid (paid add-on); email on success or failure. | Feed history tab; email on success, delivery or repeated failure. |
| Analytics | Product URL directive accepts a literal query string, so UTM parameters can be appended by hand. | Analytics tab with UTM fields. | UTM fields plus click and order reports with revenue per feed. |
| Performance | Batch mode with checkpoint and resume; automatic batch switch near time or memory limits; file locks on a shared filesystem. | Batch size; multi-process generation with `pcntl`. | Per-export caching; database-backed per-feed locks for NFS and EFS; Parquet row groups. |
| Config portability | Clone feed. | Duplicate feed; reusable category mappings and condition-based attributes. | YAML import and export of templates, filters, dynamic attributes and variables. |
| CLI | Generate (all, one feed, one test SKU) and schedule. | List and generate profiles. | Generate, deliver, validate, cleanup, AI optimize. |
| Storefront | Schema.org microdata for Google automatic item updates, configurable and custom-option deep links, Google Ads `view_item` events. | None advertised. | None advertised. |
| Inventory | MSI optional; Local Inventory per source with reservations; backorder handling. | MSI source quantities (paid add-on). | MSI salable quantity. |
| Licensing | OSL 3.0, no gated features. | Commercial, with features gated behind subscriptions. | Commercial, with annual renewal for updates. |

## Ranked gaps

### Critical

These block ordinary merchants or are now expected of any feed extension.

1. **XML output.** Many European comparison engines and eBay, Idealo, Heureka and Skroutz accept only XML, and Google and Meta accept it as well. Proposed approach: a second writer selected per feed, driven by the same Columns Map (column name becomes the element name or a configured tag), with a per-feed header, item wrapper and footer, an "omit when empty" flag per column, and CDATA or entity escaping. Keep the delimited writer unchanged. Once this exists, new XML presets are configuration in [etc/mageos_shopping_feed.xml](../../etc/mageos_shopping_feed.xml) and community members can contribute them. Effort: medium.
2. **Condition-based values.** The commonest requests are "brand from manufacturer when the attribute set is X", "custom label when price is above Y or the product is in category Z", and "price adjusted by a percentage for one channel". Proposed approach: a **Conditional Value** directive whose parameter is a rule built with Magento's rule-condition classes (the same combine and product conditions used by catalog price rules), an output source (attribute, directive or static text), an optional percentage or fixed modification, and a default. Store rules per feed first; a shared entity can follow if demand appears. Effort: medium, shared with item 3.
3. **Product selection by attribute condition.** Current filters cannot express "exclude brand X", "SKU starts with", "has an image" or "parent is disabled". Proposed approach: a **Conditions** section using the same rule engine as item 2, applied to the collection where Magento can translate it and per row otherwise, plus explicit toggles for not visible products, products without images and children of disabled parents. Effort: medium.
4. **Google Merchant API submission.** Google retired the Content API in favor of the Merchant API, and both competitors push each generation to Merchant Center and create the data source. The module stops at FTP and SFTP. Proposed approach: a separate suggested package (for example `mage-os/module-shopping-feed-google-merchant-api`) so the Google SDK stays out of the core module, with per-feed service account key, merchant ID, feed label, target countries and language, and submission after a successful generation. Effort: medium to large, including Merchant Center acceptance testing.
5. **Per-column modifiers.** Prepend and append (including "only if not empty"), case changes, round, integer, decimal places, date format, price format with or without currency, strip tags versus HTML escape, and secure URL rewriting. The pipeline already has a clean-field stage and Limit column output, so this is a Modifiers rows editor and an ordered modifier chain applied after the mapped value is resolved. Effort: small to medium.

### Operational

Regularly requested, lower blast radius if missing.

6. **Preview of the first N rows** in the Admin, in addition to the single-SKU Test Feed.
7. **Schedule granularity.** Day-of-week plus hour and minute, or a cron expression with a plain-language preview. The current hourly queue job limits precision to the hour.
8. **Notifications and history.** Email on failure (and optionally success) and a generation-history grid with source (Admin, cron, CLI), counts, duration and outcome. Today only the per-feed log file records this.
9. **Test connection** button for FTP and SFTP destinations, and a visible passive-mode toggle for FTP.
10. **Parallel generation and distributed locks.** One queue item per worker run with file locks that require a shared lock filesystem. Multi-process generation and database-backed locks matter for catalogs above a few hundred thousand rows and for multi-node hosting.
11. **UTM fields.** The Product URL directive already accepts a query string. A labeled Analytics group (source, medium, campaign, term, content) that writes the same parameter removes the guesswork. Click and order reporting is lower priority.
12. **Reusable configuration.** Category mappings and conditional values shared across feeds, and export or import of a feed definition for moving between staging and production.
13. **Preset length limits applied by default.** The presets declare title and description limits under a key the editor and generator do not read, so new feeds start with no limits. Fix the key or seed Limit column output rows from the preset.
14. **Image variants.** A resize parameter on the image directives for channels that want a fixed size, using Magento's image processor so remote media storage keeps working.
15. **Product URL path choice.** Shortest or longest category path versus the plain product URL.

### Not planned

- **Template count.** Mirasvit's 130+ templates and Amasty's Amazon, eBay, Rakuten and Idealo presets are mostly configuration. They become cheap once XML output exists and are better sourced from merchants who use those channels than built speculatively.
- **AI feed optimization.** Mirasvit's title rewriting and AI category mapping are a separate product with recurring API cost. Out of scope for the core module.
- **Click and revenue reporting.** Useful, but merchants already have this in Merchant Center, Meta Commerce Manager and analytics tools.

## Strengths to protect

Neither competitor matches this set, and the gap work above should not regress it:

- Google Local Inventory per MSI source with reservations and store-code mapping.
- The Google Promotions companion feed generated from cart price rules.
- Storefront schema.org microdata for Google automatic item updates.
- Configurable and custom-option deep links on Luma and Hyvä, and Google Ads `view_item` events.
- The shipping directive with offline carrier quotes per country.
- SFTP host-key pinning and encrypted upload credentials.
- An OSL 3.0 license with no gated features and no renewal fee.

## Suggested sequencing

| Phase | Scope | Why this order |
| --- | --- | --- |
| 1 | Inheritance how-to, inheritance mode on Columns Map rows, bundle inheritance, preset limits applied by default (items under "The reported gap" and item 13) | Small changes that answer the live report and a documented defect. |
| 2 | Rule-condition engine, then Conditions section (item 3) and Conditional Value directive (item 2); per-column modifiers (item 5) | Shares one engine across two critical gaps and completes the transformation story. |
| 3 | XML writer and the first XML presets (item 1) | Unlocks the long tail of channels and most template requests. |
| 4 | Merchant API companion package (item 4) | Largest external dependency and acceptance cost; benefits from the format and modifier work being stable. |
| 5 | Operational items 6 through 12, 14 and 15 as capacity allows | Each is independent and can ship in a point release. |

Treat the competitor details above as a snapshot. Re-read the vendor pages before quoting them externally.
