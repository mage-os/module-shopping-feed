# Google Shopping

The Google Shopping template supplies a product-data column map, Google taxonomy mapping, price filters, variant handling, shipping output, and optional promotion IDs. The merchant remains responsible for mapping those fields to the real catalog and meeting Google's current requirements.

> Documentation baseline: 1.3.0 release candidate. Last reviewed: 2026-11-12.

## Before configuration

Prepare:

* A Merchant Center account and non-serving test data source
* The target country and language
* The store view and currency that match the landing pages
* Catalog attributes for brand, GTIN or MPN, condition, and applicable variant data
* Known products covering simple, sale-price, out-of-stock, and configurable behavior

Use Google's current [product data specification](https://support.google.com/merchants/answer/7052112?hl=en) as the authority for account and item requirements.

## Configure the feed

1. Create a **Google Shopping** feed.
2. Select the target store view and currency.
3. Review the default tab delimiter and safe output path.
4. Map Magento categories to Google taxonomy values.
5. Map every identifier and variant field to the store's real attributes.
6. Review product-type, attribute-set, stock, and required-field filters.
7. Configure parent and associated-product behavior.
8. Configure Shipping only if the feed should calculate supported Magento methods.

## Critical column checks

Test at least:

* `id`
* `title`
* `description`
* `link`
* `image_link`
* `availability`
* `availability_date` for backorders and preorders
* `price`
* `sale_price`
* `sale_price_effective_date`
* `condition`
* `brand`
* MPN or GTIN, as applicable
* `google_product_category`
* `product_type`

For variants, test `item_group_id` and every applicable standard variant attribute. The current template also contains `item_group_title` and `variant_option`; validate all template columns with the intended data source rather than assuming every default is required.

## Identifiers

New feeds include empty MPN and GTIN mappings. Select **Identifier Attribute** and choose the catalog attribute containing the real manufacturer-assigned value. SKU is not an MPN unless the manufacturer actually uses that value. Existing saved mappings are preserved, so review older feeds that map SKU to MPN. [Google MPN requirements](https://support.google.com/merchants/answer/6324482?hl=en).

**Identifier Exists** leaves the field blank by default; Google treats an omitted value as yes. Missing brand, GTIN, or MPN data does not prove that no identifier exists. Select **Confirmed: no assigned identifiers** only for products whose manufacturer assigned none. That option writes `FALSE` only when brand, GTIN, and MPN are all empty. A supplied identifier takes precedence over the absence setting. Older comma-separated parameters no longer infer absence from missing fields. [Google identifier requirements](https://support.google.com/merchants/answer/6324478?hl=en).

The confirmation applies to every row using that directive. For a mixed catalog, map a verified per-product `identifier_exists` value or separate products with no assigned identifiers into their own feed. Do not use the confirmation to hide incomplete catalog data.

## Backorders and preorders

Map `availability_date` to the product's expected shipping date whenever `availability` can be `backorder` or `preorder`. New feeds include an empty **Static Value** placeholder for this column. Change its source to your date attribute; a static date is suitable only when it is accurate for every applicable product. Existing feeds must add the column themselves.

The writer accepts ISO dates/timestamps and Magento datetime attribute values such as `2026-10-15 09:00:00`. Values without a timezone are interpreted as UTC and exported as ISO 8601 with a timezone. Use a future date no more than one year away and show the same expected date on the landing page. Relative text such as `tomorrow`, invalid dates, expired dates, and dates beyond one year are rejected. [Google availability-date requirements](https://support.google.com/merchants/answer/6324470?hl=en).

A Google Shopping row that needs a date but lacks a valid one is skipped. **Test Feed** and the generation log explain the missing or invalid `availability_date`; the skipped count increases. The module does not invent a date or relabel the product as in stock. Generic feeds keep their own availability fields and rules.

For other availability values, a valid mapped date is normalized the same way. An invalid optional date is left blank without skipping the product.

## Prices and sale dates

The feed uses the selected store context and currency. Catalog price rules can participate when enabled. Sale dates use the configured store timezone.

Guest and all-group tier discounts available at quantity one are included. Bulk-only tiers and discounts restricted to other customer groups are excluded. A tier-only discount does not inherit dates from an expired special price. Compare the result with the product page as a signed-out visitor.

If the rendered sale price is equal to or greater than regular price, the module leaves sale price and its effective date empty. The header and row shape remain intact.

## Product links and variants

Associated configurable rows can link to the parent product with option values in the URL fragment. The storefront integration selects both swatches and dropdowns. See [Configurable product deep links](Configurable-Product-Deep-Links).

## Promotions

When a Google Promotions companion feed is enabled, add a `promotion_id` column mapped to the **Promotion ID** directive. See [Google Promotions](Google-Promotions).

## Submit safely

1. Test representative SKUs in Magento.
2. Generate the complete file without an upload destination.
3. Parse it locally and compare sampled values with Magento.
4. Submit it to a non-serving Merchant Center data source.
5. Record item count, parse errors, warnings, and disapprovals.
6. Classify each result as a module defect, catalog-data issue, account configuration issue, or accepted warning.

Do not enable a production schedule until Merchant Center has processed the exact file successfully.

## Automatic XML preset (1.3.0)

In **General**, select **Output Format: XML** and set **File Name** to a name ending in `.xml`. This channel supplies its RSS header, item wrapper, footer, and namespace rules automatically; no manual XML structure is required. Existing default mappings remain usable. Review custom compound fields when switching formats, and inspect a generated sample before changing the destination's data-source URL and format. Empty optional tags are omitted. See [XML Output](XML-Output) for setup, field conversions, and validation limits. Delimited output remains the default.
