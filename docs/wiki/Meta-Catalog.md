# Meta Catalog

> Documentation baseline: released behavior plus unreleased SHO-1, as marked below. Last reviewed: 2026-11-10.

When using the 1.2.0 UI Component editor, read [Admin UI Component forms](Admin-UI-Component-Forms). The guide separates historical 1.2.0 browser checks from 1.2.4 Mage-OS 3.6 verification and records the remaining limits. Template generation checks do not establish provider ingestion acceptance.

The **Meta Catalog (Facebook and Instagram)** template defaults to a quoted UTF-8 TSV file for a product catalog in Meta Commerce Manager. It uses the existing generation queue, Test Feed, scheduling, and upload tools. A local Mage-OS 3.5.0 demo passed generated-file, Admin preview, inventory, and storefront variant checks on 2026-09-29. Commerce Manager acceptance has not been verified for this template yet.

## Create and map the feed

Create a feed at **Catalog > Mage-OS Shopping Feed > Feeds Management**, select the Meta template, and choose the store view and currency. Review these defaults in **Columns Map**:

| Field | Default source | Review before use |
| --- | --- | --- |
| `id` | Product Id directive | Choose the identifier used by your Meta Pixel or Conversions API. Keep it stable. |
| `title`, `description` | Name and description attributes | Supply meaningful, plain-text product data. |
| `link` | Product URL with `utm_source=meta_catalog` | Check the destination and any configurable option selection. |
| `image_link`, `additional_image_link` | Product image and gallery directives | Check that Meta can fetch the images. |
| `price`, `sale_price` | Price and Sale Price directives | Output uses a currency suffix, such as `29.99 USD`. Match the landing page and tax treatment. |
| `sale_price_effective_date` | Sale Price Date Range | Confirm the period matches the actual offer. |
| `availability` | Availability directive | Internal stock values are converted to Meta's vocabulary. |
| `condition` | Static Value: `new` | Map a real attribute if products are used or refurbished. |
| `brand` | Manufacturer attribute | Change the attribute if your catalog stores brand elsewhere. |
| `gtin`, `mpn` | Identifier Attribute, initially unmapped | Choose real identifiers. Do not use a store SKU as an invented GTIN or MPN. |
| `item_group_id` | Parent SKU | Configurable children share their parent's group. |
| `color`, `size`, `material`, `pattern`, `gender`, `age_group` | Variant Attributes | Review attribute choices and destination requirements for your category. |
| `google_product_category` | Taxonomy by Magento Category | Configure the Categories Map where applicable. |
| `product_type` | Magento category path | Review the path depth. |

The template exports configurable children with parent context and enables complex-product context prioritization to keep grouping consistent. Check representative parent and child products with **Test Feed**. Grouped, bundle, and custom-option behavior remains configurable in the corresponding editor sections.

## Availability

| Internal stock value | Meta output |
| --- | --- |
| `in_stock` | `in stock` |
| `out_of_stock` | `out of stock` |
| `backorder` | `out of stock` |
| `preorder` | `out of stock` |

For an alternate stock attribute, use the internal values above. The shared stock mapper also accepts `in stock` and `out of stock`. Out-of-stock filtering runs before the Meta formatting step, including for configurable children. Backorders and preorders export as `out of stock` until available. The current Commerce Manager product-feed specification lists only these two output values; the broader Catalog API vocabulary does not apply to this preset.

Google-only fields such as `identifier_exists`, `availability_date`, and `promotion_id` are not part of the Meta preset. A Meta backorder does not use Google's future availability-date rule.

## Validation and review

Generation and Test Feed reject rows with missing or repeated required values in `id`, `title`, `description`, `availability`, `condition`, `price`, `link`, `image_link`, or `brand`. Condition and availability must use the supported values. Prices must be positive amounts with two decimal places and an uppercase currency suffix; product and primary-image links must be absolute HTTP or HTTPS URLs.

The generator logs each rejection and increments the skipped count. It warns when GTIN and MPN are both empty. This warning leaves the row in the output because Meta lists these identifiers as optional. Brand is required. Sale prices equal to or greater than the regular price are omitted along with their effective dates.

The built-in row validator does not check title or description length. Preset output limits are not applied automatically in 1.2.4; add explicit [Limit column output](Filters-and-Transformations#limit-column-output) rows based on the current recipient requirements. Unlike the other catalog validators, Meta can export overlong text without a local rejection.

The built-in row validator does not verify real ISO currency membership, image dimensions or fetchability, GTIN validity, every category-specific field, account eligibility, policy compliance, or landing-page parity. The demo acceptance run separately checked USD, image dimensions, local HTTP responses, and landing-page prices for its fixtures. Review your generated file and Commerce Manager diagnostics before enabling a production schedule.

## Delivery

1. Generate and inspect the file with uploads disabled. The default filename is `mageos_meta_catalog_<feed_id>.tsv` under `pub/media/mageos-shopping-feed`.
2. In Commerce Manager, open the product catalog's data sources and add a data feed. Supply the publicly reachable HTTPS URL for the generated file.
3. Set the module's generation schedule to finish before Meta's scheduled fetch. Select a cadence that keeps stock and prices current.
4. Review Commerce Manager's import diagnostics and test several product links, variants, images, and prices.
5. If the application does not serve the file publicly, use the existing FTP/SFTP upload support to place it on a merchant-controlled host, then supply that host's URL to Meta.

The template does not install Meta Pixel, configure Conversions API, create a Meta account, or synchronize orders. Existing Google Ads tracking remains separate.

## Specification references

Meta's [product data specification](https://www.facebook.com/business/help/120325381656392), [image requirements](https://www.facebook.com/business/help/686259348512056), and [variant rules](https://www.facebook.com/business/help/2256580051262113) were verified in a browser on 2026-09-29. The product-feed specification requires brand and limits availability to `in stock` and `out of stock`.

The initial implementation also consulted Meta's public [Magento feed builder](https://github.com/facebookincubator/facebook-for-magento2/blob/main/Model/Product/Feed/Builder.php) and [Business SDK](https://github.com/facebook/facebook-php-business-sdk/blob/main/src/FacebookAds/Object/Values/ProductItemAvailabilityValues.php). The current product-feed specification takes precedence where the SDK's API values differ. Local validation does not replace an actual Commerce Manager import.

## Automatic XML preset (unreleased SHO-1)

In **General**, select **Output Format: XML** and set **File Name** to a name ending in `.xml`. This channel supplies its RSS header, item wrapper, footer, and namespace rules automatically; no manual XML structure is required. Existing default mappings remain usable. Review custom compound fields when switching formats, and inspect a generated sample before changing the destination's data-source URL and format. Empty optional tags are omitted. See [XML Output](XML-Output) for setup, field conversions, and validation limits. Delimited output remains the default.
