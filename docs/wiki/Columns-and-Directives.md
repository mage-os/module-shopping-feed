# Columns and directives

Columns Map controls the output schema. Each row gives the output column a name and maps it to a Magento product attribute or a module directive.

> Documentation baseline: released behavior plus unreleased SHO-2 and SHO-3, as marked below. Last reviewed: 2026-11-10.

## How a column is built

A column has:

* An output name, such as `price`, `availability`, or `brand`
* A source attribute or directive
* An order
* An optional parameter used by that directive

Save the feed after changing Columns Map. Several filters and inheritance controls only list columns already present in the saved map.

Since 1.2.5, dropdown and multiselect option labels are read in the feed's **Store View**, falling back to the admin label when that store has no override. Multiselect output keeps the selected values' order and joins their labels with `, `. Cached labels are separate for each store, and option rows from custom source models without a usable value are skipped. Releases up to 1.2.4 always use the admin labels; sources that ignore the attribute store, such as country of manufacture, keep the process locale's labels.

For the 1.2.0 UI Component editor, use single-line output names without tabs or other control characters. The editor rejects those characters at model save and preserves literal values, structured parameters, and null defaults. The historical 1.2.0 `mageos-latest` deployment also fixes null product-URL parameters under PHP 8.4 error handling. See [Admin UI Component forms](Admin-UI-Component-Forms) for deployment status and test limits.

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

## Conditional Value (unreleased, SHO-2)

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

Google Shopping and Local Inventory leave `sale_price` and `sale_price_effective_date` empty when the rendered sale price is not lower than the regular price. The header remains present, and every row must retain the same number of positional columns.

Generic feeds preserve their custom names, ordering, and values. Google sale-price suppression does not apply to Generic output. See [Generic feeds](Generic-Feeds) for CSV quoting and [Google Shopping](Google-Shopping) for identifier and backorder-date configuration.

## Verification

After changing the map:

1. Test one simple product and one applicable complex product.
2. Parse a complete generated file with the configured delimiter.
3. Confirm every row has the same number of columns as the header.
4. Confirm empty values do not remove positional columns.
5. Compare identifiers, URLs, prices, availability, and variant fields with Magento.
