# Pinterest Catalog

> Documentation baseline: 1.3.0 release candidate. Last reviewed: 2026-11-12.

The **Pinterest Catalog** preset defaults to quoted UTF-8 TSV for a primary retail catalog. It uses the module's generation, Test Feed, scheduling, and upload tools. Pinterest import acceptance has not been verified.

## Create and map the feed

Choose the preset in **Catalog > Mage-OS Shopping Feed > Feeds Management**, select a store view and currency, then review **Columns Map**. The default file is `mageos_pinterest_catalog_<feed_id>.tsv` in `pub/media/mageos-shopping-feed`.

The default map has 23 fields. Product IDs default to Magento IDs; keep them stable and aligned with tracking. Configurable children share their parent's SKU in `item_group_id`, retain their color and size, and link to the parent page with option fragments. Product links include `utm_source=pinterest_catalog`. Generation rejects configurable output without a group ID. Review grouping when using custom options or custom mappers.

Brand defaults to Manufacturer but is optional. Condition defaults to `new`; map the actual condition for used or refurbished products. GTIN and MPN start unmapped. Use real manufacturer identifiers when available. Missing both produces a review warning, not a fabricated identifier.

Map `google_product_category` to the most specific accurate taxonomy value. An empty value produces a local warning without rejecting the product. Pinterest may also recommend more detail for a broad category even when the file is valid.

## Output and validation

| Area | Preset behavior |
| --- | --- |
| Required values | ID, title, description, link, image link, price, availability; group ID for configurable output |
| Stock | In-stock and sold-out values use spaces; preorder is retained; backorders become `out of stock` |
| Prices | Positive amounts with currency, such as `49.95 USD`; discounted prices use the same currency |
| Sales | Equal or higher sale prices are omitted with their dates. An undated discount produces a refresh warning; supplied dates must form a valid ISO 8601 interval |
| Text | The preset declares 500-character title and 10,000-character description limits, but they are not applied automatically; add them under [Limit column output](Filters-and-Transformations#limit-column-output). IDs are limited to 127 characters; product links to 511 |
| Categories | One merchant path, at most five levels; mapped Google taxonomy is preserved separately |
| Images | One primary URL and up to ten comma-separated additional URLs; each image URL is limited to 2,000 characters |

The validator rejects malformed required values, invalid UTF-8 or control characters, unsupported enums, overlong mapped values, malformed dates, and invalid HTTP(S) URLs. Optional variant-name/value lists must have matching entries. Quotes, commas, and Unicode survive the TSV writer. The field cleaner removes description markup before validation.

The implementation uses `sale_price_effective_date`, the spelling in Pinterest's [API schema and ingestion diagnostics](https://github.com/pinterest/api-description/blob/main/v5/openapi.yaml). The Help Center currently labels this field `sale_price_effective_date_attribute`. Confirm date handling with destination diagnostics and keep exported sale prices current.

## Images and catalog review

Pinterest's specification calls for primary images at least 1000 by 1500 pixels and additional images at least 75 by 75. Use actual product imagery and change the URL when replacing an image. Encode commas within image URLs so they cannot be mistaken for list separators.

Generation checks URL syntax without fetching images. It does not verify dimensions, public access, placeholder imagery, identifier authenticity, currency membership, uniqueness across rows, landing-page prices, or merchant eligibility. Check these against the generated file and Pinterest diagnostics. Tax and shipping treatment must match the actual market and storefront.

## Delivery

1. Generate with uploads disabled and use **Test Feed** for representative products.
2. Confirm the Pinterest business account, claimed website, tracking setup, and merchant requirements.
3. Under **Catalogs and product groups > Add data source**, choose **Provide URL link**, enter the public feed URL, and select the format matching the generated file: TSV by default, or XML with 1.3.0. Hosted URLs must use port 80 or 443.
4. Review country, language, and currency before creating Pins. Pinterest does not allow changing the source's country and language afterward.
5. Schedule generation before Pinterest's daily ingestion. Use **Test your data source** with a header and sample rows, then review catalog diagnostics.

The preset does not configure the Pinterest tag, create campaigns, or synchronize orders.

## Supplemental sources

A supplemental source enriches an existing primary source. Create a separate **Generic** feed with its own filename, map the same product IDs, and follow the exact fields for the supplemental type selected in Pinterest. A regional source needs `id`, `region_id`, and at least one of price, sale price, or availability. Review region definitions and landing-page behavior first.

Use Generic for a narrow supplemental file because the Pinterest preset validates a complete primary row. Match stock vocabulary, currency formatting, product filters, and IDs to the primary feed. This is a manually configured pattern; the module does not infer regions or create supplemental sources in Pinterest. Country and language supplements have different schemas.

## References

Checked on 2026-09-29 against the [retail catalog specification](https://help.pinterest.com/en/business/article/before-you-get-started-with-catalogs), [data source setup](https://help.pinterest.com/en/business/article/data-source-ingestion), [supplemental source instructions](https://help.pinterest.com/en/business/article/add-a-supplemental-data-source), and official [TSV example](https://s.pinimg.com/sub/helpcenter/assets/pinterest_product_sample_tsv_feed.tsv). Current primary sources take precedence over third-party recommendations. Local checks do not establish Pinterest acceptance.

## Automatic XML preset (1.3.0)

In **General**, select **Output Format: XML** and set **File Name** to a name ending in `.xml`. This channel supplies its RSS header, item wrapper, footer, and namespace rules automatically; no manual XML structure is required. Existing default mappings remain usable. Review custom compound fields when switching formats, and inspect a generated sample before changing the destination's data-source URL and format. Empty optional tags are omitted. See [XML Output](XML-Output) for setup, field conversions, and validation limits. Delimited output remains the default.
