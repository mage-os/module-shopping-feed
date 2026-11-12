# Columns and directives

Columns Map controls the output schema. Each row gives the output column a name and maps it to a Magento product attribute or a module directive.

> Documentation baseline: 1.3.0 release candidate. Last reviewed: 2026-11-12.

## How a column is built

A column has:

* An output name, such as `price`, `availability`, or `brand`
* A source attribute or directive
* An order
* An optional parameter used by that directive

Save the feed after changing Columns Map. Several filters and inheritance controls only list columns already present in the saved map.

Since 1.2.5, dropdown and multiselect option labels are read in the feed's **Store View**, falling back to the admin label when that store has no override. Multiselect output keeps the selected values' order and joins their labels with `, `. Cached labels are separate for each store, and option rows from custom source models without a usable value are skipped. Releases up to 1.2.4 always use the admin labels; sources that ignore the attribute store, such as country of manufacture, keep the process locale's labels.

For the 1.2.0 UI Component editor, use single-line output names without tabs or other control characters. The editor rejects those characters at model save and preserves literal values, structured parameters, and null defaults. The historical 1.2.0 `mageos-latest` deployment also fixes null product-URL parameters under PHP 8.4 error handling. See [Admin UI Component forms](Admin-UI-Component-Forms) for deployment status and test limits.

## Per-column modifiers (1.3.0)

In **Columns Map > Column modifiers (top to bottom)**, add one row per operation. Enter the exact output column name, select a modifier, and enter its value where needed. Drag rows, or use the keyboard to edit **Order**. Lower numbers run first; save and reopen to see the updated sequence. The name can refer to a column you are adding in the same save. Multiple rows for the same column form its chain; rows for other columns do not interrupt that order. Saving rejects names absent from the effective output map. Google Shopping accepts both legacy `promotions_id` and canonical `promotion_id` for the same output column, plus variant columns such as `color` added automatically when `item_group_id` is mapped. Names must match exactly, including spaces and case; fix a mistyped name rather than relying on silent trimming.

| Modifier | Value and behavior |
| --- | --- |
| Prepend / Append | Literal text, including spaces and `0`. Applies even to an empty cell. |
| Lowercase / Uppercase / Title case | Unicode case conversion. Leave Value blank. |
| Round | Decimal precision from `0` to `12`; ties round away from zero. Uses plain decimal text without exponents or negative zero, independent of PHP's `precision` setting. Trailing zeros are not padded. |
| Integer (truncate) | Removes the fractional part toward zero. Leave Value blank. |
| Decimal places | Precision from `0` to `12`, with rounding and trailing zeros. No thousands separator. |
| Date format | PHP date format, for example `Y-m-d`, `d/m/Y` or `c`. Input must be `YYYY-MM-DD` or that date plus `HH:MM:SS`, separated by a space or `T`, optionally ending in `Z` or an offset such as `-05:00`. Missing time means midnight UTC; explicit offsets are retained. Relative dates and impossible dates stay unchanged. |
| Price format | Two decimal places, a period separator, no grouping, and an ISO currency suffix. Enter a three-letter uppercase code, or leave blank to retain an existing suffix or use the feed currency. This formats the amount; it does not convert currencies. |
| Strip tags | Removes literal HTML tags, discards script/style blocks and their contents, and retains other text and existing entities. It does not decode entity-encoded tags. Uses the default cleaner's word boundaries: paragraphs, line breaks, list items, and comments leave spaces between text. Unfinished tags and unclosed script/style blocks are removed using the same rules as default cleaning. Leave Value blank. |
| Escape HTML | Encodes tags, quotes, and ampersands while retaining already encoded entities. For delimited output, delimiters containing any ASCII letter, digit, `&`, `#`, or `;` require a cell enclosure, including mixed delimiters such as semicolon-space, `;|`, and `|&`; save and generation reject unsafe configurations. Leave Value blank. |
| Secure URL | Changes a leading `http://` or `//` to HTTPS. Preserves path, query, fragment and explicit port. Other schemes and relative paths stay unchanged. It does not check whether the destination supports HTTPS. Leave Value blank. |

Numeric modifiers accept plain numbers or numbers with the uppercase currency suffix used by price directives, for example `12.50 USD`. Round, Integer and Decimal places retain that suffix. Blank, nonnumeric, and nonfinite values stay unchanged. These operations use PHP floating-point arithmetic, not arbitrary-precision decimal arithmetic.

The chain receives the mapped value after fallback, inheritance, custom-option processing, and built-in directive formatting. Price directives may therefore already have rounded to two decimal places. Use a raw numeric attribute or Conditional Value output when more source precision is needed. The chain runs once per final cell, including each member of a repeated column, before **Skip products with empty column** checks and **Limit column output**. A prepend can fill a formerly empty cell. Limits count the final text, including escape entities, and may truncate an entity. Put modifiers in the order the recipient needs.

Existing find/replace rules still run at their current mapping/formatting stage. Columns with an explicit Strip tags or Escape HTML row bypass the cleaner's default markup removal and entity decoding so the selected operation can see the source markup. Other columns keep their existing cleaning. Modifier text that introduces row or unquoted field separators is normalized before writing. For Escape HTML with a semicolon delimiter, set **Cell Enclosure** to a double quote so entity semicolons survive as part of the cell. This also applies when even one delimiter character belongs to the entity alphabet described above: a separator can overlap an entity boundary. Comma (automatically enclosed for Generic feeds), tab, pipe, and double pipe remain supported. Escape HTML produces literal HTML entities in the output value. XML always uses UTF-8 and ignores delimited-output settings; its serializer escapes the resulting text again as needed so parsing XML recovers those literal HTML entities. Modifiers preserve structured XML lists and compound groups and apply to each scalar member, including each shipping component. A blanket modifier on shipping affects every component, so check the resulting recipient values.

Example: add Prepend with `Brand: ` for `title`, followed by Uppercase for `title`. A mapped value of `Red shoe` becomes `BRAND: RED SHOE`. Reversing those rows produces `Brand: RED SHOE`.

Save, reopen, check row order and values, then use **Test Feed** and inspect the generated file. Validate configuration again on import; unsupported operations, malformed row lists, and invalid precision/date/currency arguments are rejected. See the [SHO-5 verification record](https://github.com/mage-os-lab/module-shopping-feed/blob/release/1.3.0/docs/reviews/2026-11-11-sho-5-modifiers.md) for the checks performed and remaining acceptance work.

With 1.3.0 XML, mapped names must also be valid XML element names and any prefixes must be declared. Channel presets supply their namespace rules automatically; Generic feeds require manual wrappers. See [XML Output](XML-Output).

## Common directives

| Directive | Purpose |
| --- | --- |
| Static Value | Writes the configured parameter into every applicable row. |
| Product Id | Writes a product identifier using the selected identifier mode. |
| Product URL | Builds a store-view URL and can add configured parameters. |
| Price | Maps the regular or calculated product price and formats its currency. |
| Tier Price | Maps a tier available for one unit for the selected customer group or all groups. Bulk-only tiers are excluded; no applicable tier leaves the field empty. |
| Sale Price | Maps a lower active sale price. |
| Sale Price Date Range | Maps the active special-price date range. |
| Availability | Maps stock state using default inventory or the configured availability attribute. |
| Inventory Count | Maps quantity, optionally using reservations. |
| Stock Attribute | Maps a selected stock-item value such as backorders. |
| Product Image URL | Maps the selected product image role. |
| Product Additional Images URLs | Maps additional image URLs. |
| Product Category Image URL | Uses the first applicable category image. |
| Product Expiration in Feed | Produces an expiration date from the configured number of days. |
| Variant Attributes | Maps one or more selected variant attributes. |
| Product Review Average | Maps the product's review average. |
| Product Review Count | Maps the product's review count. |
| Concatenate Attributes | Renders a text pattern containing product attributes. |
| Product Option | Maps custom-option values. |
| Shipping | Uses the feed's Shipping section to calculate output. |
| Shipping Weight | Maps and converts the shipping weight. |
| Is Bundle | Writes whether the row represents a bundle product. |
| Type by Magento Category Path | Writes a Magento category path to the configured depth. |
| Type by Magento Category | Writes the type value saved in Categories Map. |

## Conditional Value (1.3.0)

**Conditional Value** is available in Columns Map and Replace Empty Values for all eight feed types. Configure one All/Any group with 1 to 50 conditions, plus **If matching** and **Otherwise** outputs. Outputs can be fixed text, a product attribute, or a percentage adjustment of a raw numeric attribute. New rows default to Static Value; select Conditional Value explicitly.

See the [Conditional values guide](Conditional-Values) for a complete setup example, calculations, empty-value behavior, parent context, testing, and current verification limits. Parent conditions use only the active mapping parent; standalone catalog-parent lookup belongs to [Product Selection](Product-Selection). Normal column transformations and inheritance still apply.

## Google Shopping directives

Google Shopping adds:

| Directive | Purpose |
| --- | --- |
| Adwords Price Buckets | Maps a value from the price-range rules configured under Product Filters. The Admin retains the historical field name. |
| Identifier Exists | Leaves unknown/present identifiers unspecified; writes FALSE only after explicit absence confirmation and when brand, GTIN, and MPN are empty. |
| Identifier Attribute | Maps an identifier from a selected attribute. |
| Item Group ID | Groups related variants under a stable parent-derived value. |
| Item Group Title | Maps the module's item-group title value. |
| Variant Option | Maps the module's variant-option value. |
| Taxonomy by Magento Category | Uses the Google taxonomy value saved in Categories Map. |
| Promotion ID | Adds IDs from the Google Promotions configuration. |

The correct output column is `promotion_id`, singular. Do not restore the historical `promotions_id` header.

## Google Shopping defaults

The current template includes columns for product identity, item grouping, `color`, `size`, `material`, `pattern`, `gender`, `age_group`, title, description, URL, images, price, sale price, sale dates, availability, availability date, weight, brand, MPN, GTIN, condition, Magento product type, Google product category, identifier state, bundle state, shipping, and promotion IDs.

Template defaults still require review. Map `brand`, MPN, GTIN, apparel attributes, and categories to the real catalog attributes used by the target store. Confirm the current requirements in Google's [product data specification](https://support.google.com/merchants/answer/7052112?hl=en).

## Local Inventory directives

Google Local Inventory adds **Inventory Source**, which writes the Google store code mapped from the active MSI source. An unmapped source keeps its Magento source code.

Its default columns are:

* `store_code`
* `id`
* `availability`
* `price`
* `sale_price`
* `sale_price_effective_date`
* `quantity`

## Sale-price behavior

Google Shopping and Local Inventory leave `sale_price` and `sale_price_effective_date` empty when the rendered sale price is not lower than the regular price. In delimited output, the header remains present and every row retains the same number of positional columns. With 1.3.0 XML presets, empty optional fields are omitted as described in [XML Output](XML-Output).

Generic feeds preserve their custom names, ordering, and values. Google sale-price suppression does not apply to Generic output. See [Generic feeds](Generic-Feeds) for CSV quoting and [Google Shopping](Google-Shopping) for identifier and backorder-date configuration.

## Verification

After changing the map:

1. Test one simple product and one applicable complex product.
2. Parse a complete generated file with the configured delimiter.
3. Confirm every row has the same number of columns as the header.
4. Confirm empty values do not remove positional columns.
5. Compare identifiers, URLs, prices, availability, and variant fields with Magento.
