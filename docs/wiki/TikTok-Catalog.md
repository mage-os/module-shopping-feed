# TikTok Catalog

> Documentation baseline: 1.3.0 release candidate. Last reviewed: 2026-11-12.

The **TikTok Catalog** preset defaults to a quoted UTF-8 CSV file for product catalogs in TikTok Ads Manager. It shares the module's generation, Test Feed, scheduling, and upload tools. An actual TikTok catalog import has not been verified.

## Create and map the feed

Choose the preset in **Catalog > Mage-OS Shopping Feed > Feeds Management**, select a store view and currency, then review **Columns Map**. The default file is `mageos_tiktok_catalog_<feed_id>.csv` in `pub/media/mageos-shopping-feed`.

The default map has 24 fields. TikTok uses `sku_id` as its required product identifier. This defaults to the Magento product ID; keep the mapping stable and match it to your TikTok Pixel content IDs. Configurable children share their parent's SKU in `item_group_id`, with distinct option fragments in their product links. Links include `utm_source=tiktok_catalog`.

Brand defaults to Manufacturer and is required. Map real manufacturer-assigned GTINs and MPNs; they start unmapped. Do not invent identifiers or substitute your store's name for another manufacturer's brand. The optional `video_link` starts empty; map a publicly reachable video URL if available.

Map `google_product_category` to a reviewed taxonomy value. An empty value produces a local warning without rejecting the product; a custom `product_type` path alone can still receive TikTok's category recommendation.

## Destination behavior

| Area | Preset behavior |
| --- | --- |
| Required fields | `sku_id`, `title`, `description`, `availability`, `condition`, `price`, `link`, `image_link`, `brand` |
| Stock | `in_stock` becomes `in stock`; `out_of_stock` becomes `out of stock`; `backorder` becomes `available for order`; `preorder` stays `preorder` |
| Prices | Regular and sale prices include currency, such as `49.95 USD` |
| Sales | Sale prices at or above regular price are omitted with their dates. A discount may have no end date; a supplied date must be a valid ISO 8601 start/end interval |
| Text | The preset declares 150-character title and 20,000-character description limits, but they are not applied automatically; add them under [Limit column output](Filters-and-Transformations#limit-column-output). CSV quoting preserves commas, quotation marks, and Unicode |
| Categories | The first product category path and mapped Google taxonomy are limited to the first three levels |
| Images | Primary and additional image links must use JPG, JPEG, or PNG paths. Up to ten additional URLs are separated by commas inside a quoted CSV cell |
| Variants | Children export with parent context and their mapped color, size, and other attributes |

**Keep sale prices current.** TikTok documents that `sale_price_effective_date` does not currently expire discounts in the catalog. Regenerate and refresh the feed when an offer ends so the exported price matches the landing page. Do not rely on the date field alone.

Missing required values, unsupported enums, malformed prices or dates, invalid links, invalid UTF-8, and control characters cause rows to be skipped with messages in the generation log and Test Feed. GTINs must contain 8, 12, 13, or 14 digits; convert ISBN-10 to ISBN-13. Missing both GTIN and MPN produces a review warning. Title symbols produce a warning to review emoji; this is not a complete emoji detector.

The validator does not verify identifier authenticity or check digits, currency membership, cross-row uniqueness, image dimensions or public fetchability, video formats, policy compliance, or landing-page parity. Review those against the actual catalog. Use clear product images at least 500 by 500 pixels for carousel ads, and review TikTok's creative requirements for your placement.

## Delivery

1. Generate the file with uploads disabled and use **Test Feed** for representative products.
2. In TikTok Ads Manager, open **Assets > Catalog** and create or select a product catalog. Confirm account and market availability, targeting location, and currency.
3. Under **Products > Add Products**, choose **Data Feed Schedule** and supply the publicly reachable CSV URL.
4. Choose the intended update method and schedule the destination fetch after feed generation. Refresh at least daily and whenever prices or availability change.
5. Review import diagnostics and verify IDs, variants, prices, images, and landing pages before using the catalog in ads.

The preset does not create campaigns, configure TikTok Pixel, or synchronize TikTok Shop inventory and orders.

## Specification references

Checked on 2026-09-29 against TikTok's [catalog product parameters](https://ads.tiktok.com/resources/help/article/catalog-product-parameters?lang=en), [catalog creation and scheduled feed instructions](https://ads.tiktok.com/resources/help/article/create-manage-catalogs?lang=en), and [catalog quality guidance](https://ads.tiktok.com/resources/help/article/best-practices-for-a-high-quality-catalog). Current provider specifications take precedence over third-party recommendations. Local validation does not establish destination acceptance.

## Automatic XML preset (1.3.0)

In **General**, select **Output Format: XML** and set **File Name** to a name ending in `.xml`. This channel supplies its RSS header, item wrapper, footer, and namespace rules automatically; no manual XML structure is required. Existing default mappings remain usable. Review custom compound fields when switching formats, and inspect a generated sample before changing the destination's data-source URL and format. Empty optional tags are omitted. See [XML Output](XML-Output) for setup, field conversions, and validation limits. Delimited output remains the default.
