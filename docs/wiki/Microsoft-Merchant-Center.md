# Microsoft Merchant Center

> Documentation baseline: 1.3.0 release candidate. Last reviewed: 2026-11-12.

When using the 1.2.0 UI Component editor, read [Admin UI Component forms](Admin-UI-Component-Forms). The guide separates historical 1.2.0 browser checks from 1.2.4 Mage-OS 3.6 verification and records the remaining limits. Template generation checks do not establish provider ingestion acceptance.

The **Microsoft Merchant Center** preset defaults to a UTF-8 tab-delimited `.txt` file. With XML output in 1.3.0, it also provides an automatic RSS XML preset: select **Output Format: XML** and use a `.xml` file name. The module supplies the XML structure. It shares the module's generation, Test Feed, scheduling, and upload tools. Microsoft import acceptance has not been verified.

## Create and map the feed

Choose the preset in **Catalog > Mage-OS Shopping Feed > Feeds Management**, select a store view and currency, then review **Columns Map**. The default file is `mageos_microsoft_<feed_id>.txt` in `pub/media/mageos-shopping-feed`.

The default map contains 24 fields. Product IDs stay stable; configurable children share their parent's SKU in `item_group_id`. Product URLs include `utm_source=microsoft_merchant_center` and option fragments for configurable selection. Category mapping uses the existing taxonomy provider and writes Microsoft's `product_category` field. Review every mapping against your catalog.

Map manufacturer-assigned GTINs and MPNs to real attributes. They start unmapped. The **Identifier Exists** directive offers explicit confirmation for products with no assigned identifiers; missing catalog data does not set it to FALSE automatically. Brand defaults to Manufacturer. Do not invent identifiers or use your store name as a brand unless you manufacture the item.

Delimited output emits UTF-8 without a byte-order mark (BOM) and places the required `id` column last so empty optional values do not produce trailing tabs. It does not enclose cells in quotes. Existing field cleaning removes markup and embedded tab/newline separators; Unicode and embedded quotation marks remain readable. XML output uses the preset's namespace, omits empty tags, and repeats additional-image elements. See [XML Output](XML-Output) for its mapping rules.

## Destination behavior

| Area | Preset behavior |
| --- | --- |
| Stock | `in_stock` becomes `in stock`; `out_of_stock` and `backorder` become `out of stock`; `preorder` remains `preorder` |
| Prices | Regular price includes currency, such as `49.95 USD`; sale price is numeric, such as `39.95`, in the same feed currency |
| Sales | Map a dated offer; active sale values require an ISO 8601 start/end interval. Values at or above the regular price are omitted with their dates |
| Text | The preset declares 150-character title and 10,000-character description limits, but they are not applied automatically; add them under [Limit column output](Filters-and-Transformations#limit-column-output). IDs and group IDs are validated against a 50-character limit |
| Identifiers | Missing all identifiers on a new product requires explicit absence confirmation; incomplete manufacturer data produces warnings |
| Variants | Children are exported with parent context, distinct URLs, and their variant attributes |

The preset requires explicit availability and condition even though Microsoft has defaults for these fields. This prevents an unmapped stock value from silently becoming available. Unsupported values, invalid URLs, invalid price formats, overlong values, repeated cell values, and malformed sale periods cause rows to be skipped with actionable logs in generation and Test Feed. Brand is limited to ten words; values above the recommended 70 characters produce a warning, and values above 1,000 are rejected. GTIN/ISBN formatting is checked, but identifier authenticity and check digits are not verified.

This initial preset requires positive prices and does not implement zero-down installment offers. It does not verify currency membership, cross-row uniqueness, image dimensions or public fetchability, category-specific requirements, target-domain ownership, or landing-page parity. Check those against your actual file and destination diagnostics.

## Country and catalog review

Match price tax treatment to the country of sale and the landing page. The destination's shipping rules, apparel attributes, and other conditional fields depend on the target market and category. The preset cannot infer them from a store view.

For Austria or Germany, configure the required shipping data before import. For a fixed rate in delimited output, add a Static Value column named `shipping(country:service:price)` with a reviewed value such as `DE:Standard:6.49`. For XML output, use a column named `shipping` with the preset's `country:region:service:price` value, for example `DE::Standard:6.49 EUR`; the writer creates nested shipping elements. Use a real attribute or custom mapper when costs vary. Review shipping mappings when changing output format.

For applicable apparel categories and target countries, map color, size, gender, and age group. Keep differentiating attributes consistent across all variants. Review adult-product restrictions, images, and assigned identifiers with Microsoft before enabling a production feed.

## Delivery

1. Generate the file with uploads disabled and use **Test Feed** for representative products.
2. Create an online product feed under **Microsoft Merchant Center > Feeds**. Match the verified store domain, country, and currency.
3. Choose **Automatically download file from URL** and supply the publicly reachable file URL. Use `.txt` for tab-delimited output or `.xml` for XML output.
4. Schedule generation before the destination fetch, keep the file current, and review import diagnostics.
5. If you use the existing upload tools, confirm the destination's protocol and filename settings first.

The template generates product data. It does not create campaigns, configure UET tracking, synchronize orders, or implement checkout.

## Specification references

The implementation was checked against Microsoft's current [product attributes](https://learn.microsoft.com/en-us/advertising/msa-help/hlp_ba_conc_aboutbingmerchantcentercatalogfile), [file creation requirements](https://learn.microsoft.com/en-us/advertising/msa-help/hlp_ba_conc_bmcwhatiscatalog), and [scheduled download guidance](https://learn.microsoft.com/en-us/advertising/msa-help/hlp_ba_proc_bmc_scheduledownloadfeed) on 2026-09-29. The dated provider specifications take precedence over third-party template recommendations. Local validation does not establish Merchant Center acceptance.
