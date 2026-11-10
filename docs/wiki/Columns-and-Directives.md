# Columns and directives

Columns Map controls the output schema. Each row gives the output column a name and maps it to a Magento product attribute or a module directive.

> Documentation baseline: release 1.2.5 (`v1.2.5`); earlier acceptance is identified by version. Last reviewed: 2026-10-22.

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

The unreleased source adds **Conditional Value** to all eight feed types. In **Columns Map**, select it as the source, then add between 1 and 50 product conditions. Choose **All conditions** or **Any condition** and configure both **If matching** and **Otherwise**. The same directive is available in **Replace Empty Values**.

New rows in both sections default to **Static Value** and allow an empty parameter. Select **Conditional Value** explicitly when you need a rule; a conditional rule requires at least one condition.

Each output supports:

| Output type | Result |
| --- | --- |
| Fixed text | The text you enter, including an explicit `0` or an empty value. |
| Product attribute | The selected product attribute, with store-view option labels for dropdowns and multiselects. |
| Percentage adjustment | A raw numeric attribute multiplied by `1 + percentage / 100`, rounded to two decimal places. `10` increases it by 10%; `-10` decreases it by 10%. |

For example, match **Attribute Set** to Clothing and return the **manufacturer** attribute for `brand`. Match **Price > 100** and return fixed text `premium` for `custom_label_0`. To reduce a raw price by 10%, choose **Percentage adjustment**, source `price`, and percentage `-10`.

Conditions use Magento's rule operators against the current feed product's store-view data. The unreleased SHO-3 shared engine lists product attributes without requiring **Use for Promo Rule Conditions** and adds SKU wildcard operators and **Has base image**. Dropdown conditions offer option labels but store their IDs. List operators take comma-separated raw values or option IDs; dates use `YYYY-MM-DD`. Category conditions use category IDs. A missing attribute only matches **is undefined**; it does not trigger a default-store product reload.

Percentage outputs do not apply tax, currency conversion, catalog-rule pricing, or the Price directive's formatting. They are appropriate for numeric exports whose destination accepts that representation. An empty source stays empty, and a nonnumeric source stops generation with an error. The permitted adjustment range is -100 through 100000 percent. Use the existing Price directive when its pricing behavior is required.

Save, reopen, and run **Test Feed** with one matching SKU and one nonmatching SKU. Confirm the output values and destination formatting before scheduling generation. Normal column text filters and empty-value replacements still apply. Parent inheritance continues to follow the feed's existing inheritance configuration; this directive evaluates whichever product is being mapped.

This version supports one All/Any condition group and two outputs per mapping. It does not provide nested groups, ordered multi-rule chains, arbitrary formulas, or references to other feed columns. See the [local verification record](https://github.com/mage-os-lab/module-shopping-feed/blob/main/docs/reviews/2026-11-09-sho-2-conditional-values.md) for the tests and remaining acceptance work.

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
