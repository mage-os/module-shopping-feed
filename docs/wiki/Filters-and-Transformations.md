# Filters and transformations

Product Filters decides which products reach the output and how selected column values are changed. Rule order matters.

> Documentation baseline: release 1.2.5 (`v1.2.5`); earlier acceptance is identified by version. Last reviewed: 2026-10-22.

## Catalog selection

### Exclude a single product

Since 1.2.5, `setup:upgrade` creates the **Skip from Being Submitted** Yes/No product attribute. On the product form, open **Shopping Feed** and set it to Yes. The attribute defaults to No and supports store-view overrides; Yes excludes the product, including configurable, grouped, and bundle children, from every feed for that store view, and the feed log names the skipped product. A store-view No restores it there.

A merchant-created `mageos_shopping_feed_skip_submit` attribute keeps its existing definition, assignments, scope, and values. The check honors the value 1, so it must be a Yes/No field. The attribute is user-managed: deleting it in Admin disables the exclusion, and a later `setup:upgrade` does not recreate it. Release 1.2.4 and earlier check this attribute but do not create it.

### Allow Out of Stock

Controls whether out-of-stock products remain eligible for the feed. Complex-product sections have separate out-of-stock controls for associated products.

### Submit only products of these types

Limits the feed to selected Magento product types. Product visibility and complex-product context still apply. A not-visible simple product may be emitted as part of an eligible configurable product.

### Submit only products that have these attribute sets

Limits output to selected product attribute sets.

## Output transformations

### Replace empty values

Fills an empty output column from a configured value or another source. The target columns must already exist in the saved Columns Map.

Replacement rules can be ordered and nested. Test the first successful source and the all-empty case.

### Find And Replace

Applies string replacement at column output. Large rule sets add work to every applicable row, so measure their effect on large catalogs.

### HTML cleanup

Version 1.2.1 removes raw and escaped HTML tags, comments, and style/script content from column values. It also decodes double-encoded entities. Literal comparisons such as `3 < 5 > 2` remain text.

Version 1.2.3 also removes unfinished tag-shaped fragments while preserving following text and literal comparisons.

Version 1.2.2's [issue #14 fix](https://github.com/mage-os-lab/module-shopping-feed/issues/14) also removes tags with stray attribute quotes while preserving the following description text. For example, `<img alt="3.5" core" /></p><h3>3.5" Thick - "Hot Flow" Options</h3>` becomes `3.5" Thick - "Hot Flow" Options`. Quoted comparisons such as `<img alt="a > b">` and `<img alt="a < b">` are removed with the image tag.

### Limit column output

Truncates selected output columns to a character limit without splitting a multibyte UTF-8 character. Since 1.2.1, limits run after output encoding, HTML cleanup, entity decoding, and whitespace cleanup. If several limits target the same column, they run in order.

New feeds start with no limit rows. The presets declare title and description limits, but releases up to and including 1.2.4 store those defaults under a key the editor and generator do not read, so they are never applied. Add the limits you need here. Pinterest, TikTok, Microsoft and OpenAI validators skip rows exceeding their local limits and list them in the feed log. Meta does not check title or description length locally, so its output also needs explicit limits and recipient review.

### Skip Products with empty

Rejects rows when selected required columns are empty. Save Columns Map before selecting fields here.

## Google-only filters

Google Shopping adds:

* **Skip Products with Price above**
* **Skip Products with Price below**
* **Adwords Price Buckets**

The upper and lower price filters do nothing when left empty. Price buckets build a value for a column mapped to the matching directive. The Admin label retains the older Adwords name, but the output is simply a configurable bucket value.

## Processing order

Think of the feed as a pipeline:

1. Select eligible catalog products.
2. Resolve each mapped column value. For text values, apply find-and-replace rules, output encoding, markup/entity cleanup, delimiter/whitespace cleanup, and then output limits.
3. If a mapped value is empty, try its configured replacement sources.
4. Apply feed-specific row filters and formatters.
5. Reject rows missing required output.

Test interacting rules together. A transformation that produces an empty value can affect a later required-field filter.

HTML entities are decoded before a final pass removes line breaks and, for output without an enclosure, the active field delimiter. An explicitly configured enclosure preserves embedded delimiters. Generic comma-delimited feeds preserve commas and quote each CSV field, including embedded quotes. Encoded tabs such as `&#09;` therefore cannot add an extra column to a tab-delimited row. Verify the complete generated file, including rows containing encoded punctuation or whitespace.

## Verification

For every filter change, keep one fixture that should be included and one that should be excluded. Use **Test Feed** first, then compare expected product and skipped counts in a complete run.

## Product selection conditions (unreleased SHO-3)

In **Product Filters > Product selection conditions**, add up to 50 conditions and choose **All conditions** or **Any condition**. Only matching output products are included. Remove every condition to disable this filter. Existing feeds without conditions retain their existing behavior.

Each condition reads either the **Product** or **Parent product** in the feed's store view. Available choices include product attributes without requiring **Use for Promo Rule Conditions**, visibility, status, category IDs, attribute set, and **Has base image**. Select options compare stored option IDs. **Date** and **Date and Time** attributes use `YYYY-MM-DD` and compare the stored calendar day, ignoring the time of day; no timezone conversion is applied. For example, `2026-03-01 15:30:00` matches `2026-03-01`. List operators accept comma-separated raw values or IDs. Price conditions compare the stored attribute value, before output currency conversion or price directives.

SKU supports **matches wildcard** and **does not match wildcard**: `*` matches zero or more characters and `?` matches one character. Matching is case-sensitive and covers the whole SKU. For example, `SALE-*` selects SKUs starting with `SALE-`; `*-BLUE` selects SKUs ending with `-BLUE`. Other punctuation is literal, not a regular expression.

**Has base image** checks whether the product's base `image` attribute contains a value other than blank or `no_selection`. It does not check whether the image file exists, inspect the media gallery, or apply column inheritance.

For associated output rows, parent conditions read the parent currently being mapped. For standalone rows, they examine direct configurable, grouped, and bundle parents assigned to the feed website, including disabled parents. If several parents exist, the complete expression must match against at least one parent; different parent conditions cannot be satisfied by different parents. A product without a parent never satisfies a parent condition, including a negative or undefined comparison. A product-only alternative in an Any rule can still match it.

Examples:

* Product **SKU matches wildcard `SALE-*`** and product **Has base image is Yes**.
* Product **Visibility is Catalog, Search** to narrow rows to that visibility.
* Parent product **Enable Product is Enabled** to require an enabled parent, or **Disabled** to select rows with a disabled parent. Products without parents fail either condition.

Selection applies before duplicate tracking and separately to parent and associated output rows. A parent failing selection does not suppress its matching children. The existing type, stock, category, visibility, status, per-product skip, and column filters still apply: conditions can narrow eligibility but cannot re-enable products those filters excluded. In particular, hidden standalone products do not become eligible merely by adding a visibility condition; hidden associated products can be filtered within their existing parent context.

The shared condition engine also powers [Conditional Value](Columns-and-Directives#conditional-value-unreleased-sho-2). Saved or imported malformed conditions are rejected, including keyed condition objects, unsupported structured attributes, and unknown option values; generation validates selection before processing products. Rules are evaluated after products are loaded, so they reduce exported rows rather than database scan counts. Parent lookups reuse a bounded cache of store-scoped parents during each generation or batch and load only attributes used by the rule. Large catalogs still need workload-specific performance testing.

After saving, reopen the feed to check its rules, then use **Test Feed** and generate a full local file. Check both included and excluded SKUs in the output and the feed log.

Selection skips count rejected output candidates, identified by product and parent context. Repeated checks of the same candidate during mapping count once. A standalone row and a row under a parent are separate candidates, so skipped rows can exceed scanned products. Logs identify the parent context when present. The counter does not estimate how many option or inventory rows a rejected candidate would have produced.

Programmatic `null` clears selection and is saved as an empty list. Numeric-looking strings such as `00123` remain distinct from `123` when rules are saved. The editor preserves legacy keyed rows for repair and saves them as a list after an edit.
