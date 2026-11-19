# Testing one product

Test mode renders feed values for a chosen product without replacing the normal feed file, changing normal feed status, uploading files, or generating a Promotions companion file.

> Documentation baseline: 1.3.0 release candidate. Last reviewed: 2026-11-12.

The [UI Component Test Feed form](Admin-UI-Component-Forms) uses the same saved-feed route and output renderer. The original deployed candidate failed previews using new category mappings. Local repairs now pass that workflow and reject malformed lookup inputs before product loading. Numeric SKUs remain strings; Product ID mode requires a positive integer.

## Test in the Admin

Use a role with **Generate Feed** (`MageOS_ShoppingFeed::generate`), then select **Test Feed** for the saved feed in **Catalog > Mage-OS Shopping Feed > Feeds Management**. In 1.2.4, grid access alone does not permit previews. A read-only grid may still show the link, but the controller denies access and editor preview buttons are hidden.

Choose whether the input is a SKU or product ID, enter the value, and select **Test Now**. The product must be enabled and visible in the catalog. To test a child of a complex product through the Admin, enter the parent SKU, then inspect the rendered child rows.

## Test from the CLI

Run:

```bash
bin/magento mage-os:shopping-feed:generate <feed_id> <sku>
```

The result prints each mapped field and reports the number of product rows. A configurable, grouped, or bundle product can produce more than one row depending on the feed's complex-product settings.

## Representative product set

One successful SKU does not prove the whole feed. Test at least:

* A regular-price simple product
* A product with an active special price and valid dates
* An out-of-stock product
* Google backorder/preorder products with valid and missing availability dates
* Products with real identifiers and confirmed identifier absence
* A product containing quotes, delimiters, line breaks, Unicode, or HTML in mapped text
* Each enabled complex-product type
* A configurable with multiple child options
* An MSI product assigned to multiple sources for Local Inventory

## Compare source to output

Check values at the configured store view and currency scope:

1. Product ID or SKU mapping
2. Parent and child identity
3. Title and description transformations
4. Regular price, sale price, and effective dates
5. Availability and quantity
6. Product and image URLs
7. Category and taxonomy values
8. Shipping and promotion identifiers where enabled

A Google row skipped for a missing or invalid availability date is explained in the test log. The preview shows cell values; CSV quotation is applied when writing the complete file. After test output is correct, generate a complete file and confirm product counts and row shape with the recipient's parser. See [Manual and CLI generation](Manual-and-CLI-Generation).

## XML preview (1.3.0)

For XML feeds, Test Feed displays mapped column values and validates their XML serialization. It does not show the complete XML document. Check the preview log for invalid product characters or malformed compound fields; these can skip an affected row. Generate a complete test file to inspect namespaces, repeated or nested elements, empty-tag omission, CDATA, and the single document header/footer. See [XML Output](XML-Output).
