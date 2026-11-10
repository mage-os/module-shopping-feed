# XML Output

> Documentation baseline: unreleased SHO-1. Last reviewed: 2026-11-10.

Select **XML** in the feed's **General > Output Format** field and set **File Name** to a name ending in `.xml`. Existing feeds default to delimited output.

Google Shopping, Google Local Inventory, Microsoft Merchant Center, Pinterest, TikTok, and Meta automatically use their destination RSS preset. Header, item element, footer, namespaces, and empty-field omission are supplied for you. Channel title and description come from the feed name; the channel link comes from the selected store's base URL. Existing Columns Map names remain unchanged, so switching between delimited and XML output does not require remapping. TikTok's `sku_id` is written as `g:id`.

The preset is loaded from the destination configuration even if the feed has saved generic XML wrappers from an earlier version. Custom wrapper settings are available only on generic feeds. OpenAI has no supported XML preset and offers delimited output only.

## Choose a format

| Feed type | XML setup |
| --- | --- |
| Google Shopping | Automatic RSS preset with Google-prefixed product fields |
| Google Local Inventory | Automatic RSS preset for inventory fields, with optional pickup-cost mapping |
| Microsoft Merchant Center | Automatic RSS preset with nested shipping/tax and repeated additional images |
| Pinterest Catalog | Automatic RSS preset with the destination's unprefixed fields |
| TikTok Catalog | Automatic RSS preset; `sku_id` becomes `g:id`; shipping/image values remain packed |
| Meta Catalog | Automatic RSS preset; repeated `additional_image_link` elements are unprefixed |
| Generic | Manual document wrapper, mapped element names, optional tags, and CDATA |
| OpenAI / ChatGPT beta | Delimited only |

These are unreleased SHO-1 capabilities, not features of version 1.2.5. Delimited output remains the default. Google Promotions is a separate companion writer; selecting XML for the product feed does not change its format.

## Switch a channel feed to XML

1. Open **Catalog > Mage-OS Shopping Feed > Feeds Management** and configure one of the six supported channel feeds. Use a test copy with no schedule or uploads while validating the change.
2. In **General**, set **Output Format** to **XML**.
3. Set **File Name** to a distinct filename ending in `.xml`, for example `google_products_%s.xml`. `%s` becomes the feed ID. The format selector does not rename the file, and a `.xml` extension by itself does not select XML output.
4. Review **Columns Map**. Keep the preset's normal column names; it supplies namespaces and supported field conversions. Do not enter an XML header, item wrapper, or footer for these channel feeds.
5. Save and reopen the feed. Use **Test Feed** for representative products, including empty optional values, punctuation, multiple images, and shipping where applicable. Review the preview log for skipped rows.
6. Generate the complete test feed and download it. Open it in an XML-aware editor or parse it, for example with `xmllint --noout google_products_123.xml`. Check the actual elements and values as well as the generation log. Test Feed shows mapped values and serialization errors, not the finished XML document.
7. Submit a sample to the destination and review its diagnostics. Configure its data source for the new format and URL, update any filename-dependent upload configuration, then resume scheduling only after acceptance.

Changing a filename changes the public feed URL. The previously generated file may still exist at its old URL; saving or generating the new format does not redirect a recipient's existing data source. To return to delimited output, select **Delimited**, restore the intended filename and delimiter settings, regenerate, and update the recipient accordingly.

## Configure a generic XML feed

For **generic feeds**, the manual XML settings are:

| Setting | Behavior |
| --- | --- |
| XML Header | XML declaration and opening document elements. Declare namespaces here. |
| XML Item Element | Element name wrapping each mapped product row, such as `product` or `item`. |
| XML Footer | Closing document elements matching the header. |
| XML Optional Columns | Comma-separated column names whose tags are omitted when their value is empty. `*` selects all columns. Zero is retained. |
| XML CDATA Columns | Comma-separated column names written as CDATA. `*` selects all columns. Other values use XML escaping. |

For generic feeds, Columns Map names become XML element names. Destination presets apply their namespace rules automatically. Pinterest uses unprefixed `title`, `description`, `link`, and `sale_price`; Meta uses unprefixed `additional_image_link`. Other preset fields use the Google namespace prefix. Use valid names such as `id`, `description`, or `g:id`; declare the `g` namespace in the header. Array values become repeated elements. Empty values produce empty elements unless the column is optional. The delimited format's Empty Cell Value, delimiter, and enclosure settings do not apply to XML.

For a simple custom catalog, select XML and a `.xml` filename, then configure:

| Field | Example value |
| --- | --- |
| XML Header | `<?xml version="1.0" encoding="UTF-8"?><products>` |
| XML Item Element | `product` |
| XML Footer | `</products>` |
| XML Optional Columns | `brand` |
| XML CDATA Columns | `description` |

In **Columns Map**, retain or add `id`, `description`, `brand`, and `quantity`, and map them to your catalog's values. Remove other columns if the recipient does not want them. A mapped row with ID `ABC-1`, description `Blue & white`, an empty brand, and zero quantity produces this structure (indentation shown for readability):

```xml
<?xml version="1.0" encoding="UTF-8"?>
<products>
  <product>
    <id>ABC-1</id>
    <description><![CDATA[Blue & white]]></description>
    <quantity>0</quantity>
  </product>
</products>
```

Blank `brand` is omitted; zero quantity is retained. Without CDATA, the ampersand would be escaped as `&amp;`. A literal `]]>` inside a CDATA value is split safely across CDATA sections. Optional and CDATA column lists control serialization; they do not create missing mappings or make destination-required fields optional. A space-separated column name such as `product name` is invalid XML; rename it to `product_name` or another name required by the recipient.

Keep the XML declaration at the very start of the header, with no leading whitespace. Header and footer must form one complete document around the item elements. If names use prefixes, declare those prefixes in the header. The Generic default wrapper is `products` with one `product` per mapped row. Review it against the recipient's specification rather than assuming it fits every engine.

## Validation and failed rows

Output uses XML 1.0 and UTF-8. The existing product text cleanup still applies before serialization. CDATA controls serialization, not whether product HTML is retained. Saving validates element names, declared namespaces, and wrapper syntax. Unicode XML names are supported. DTDs and entity declarations are prohibited. Invalid product text or malformed mapped compound values skip only the affected row; View Log and Test Feed identify the source SKU, mapped row ID, and column. Correct the product data or mapping and regenerate. Configuration and file-write errors still stop generation and preserve the previously published feed.

The header is written once at the start. The footer is written after the final batch, before the temporary file is published. An empty feed is a complete XML document. Test SKU continues to show mapped values in the admin preview and validates XML serialization; it does not display the full XML document.

## Preset configuration

An extension can configure XML output in `mageos_shopping_feed.xml`. Add these fields after `encoding` in the preset's existing `default_feed_config/output_params` section:

```xml
<format>xml</format>
<xml_header><![CDATA[<?xml version="1.0" encoding="UTF-8"?><products>]]></xml_header>
<xml_item>product</xml_item>
<xml_footer><![CDATA[</products>]]></xml_footer>
<xml_optional_columns>description,brand</xml_optional_columns>
<xml_cdata_columns>description</xml_cdata_columns>
```

Also configure `default_feed_config/file/feed` with an `.xml` filename and map the destination's element names in Columns Map. The six destination presets also set `xml_preset` to `rss`. Their canonical configuration supports `xml_field_aliases` (a JSON name map), `xml_compound_fields` (a JSON map of column names to ordered subfields), `xml_list_fields` (comma-separated names of repeated fields), and `xml_unprefixed_fields` (fields the provider expects without `g:`). These settings are extension configuration, not merchant form fields. JSON maps are decoded by the existing preset converter.

Google Shopping, Microsoft, Pinterest, and Meta convert `shipping` values in `country:region:service:price` form and `tax` values in `country:region:rate:tax_ship` form into nested elements. Multiple groups are comma-separated. Blank subfields are omitted. Malformed manually mapped groups skip the affected row with a log message. The built-in shipping mapper preserves service titles containing colons and commas. The additional-image mapper preserves individual URLs, including commas inside URLs, before rendering repeated elements. Manually supplied shipping/tax lists still use the documented separators; avoid separators inside their subfield values. TikTok retains its packed shipping/image values; Local Inventory uses its inventory fields and supports a nested `pickup_cost` when mapped. See [Google Local Inventory and MSI](Google-Local-Inventory-and-MSI) for conditional pickup requirements.

Custom XML attributes and arbitrary nested structure editing are not exposed in the admin form. Local tests verify serialization and integration; account-specific ingestion and destination acceptance still require a sample upload.

## Provider review

The Meta and Pinterest namespace rules follow their published XML samples: [Meta catalog reference](https://developers.facebook.com/docs/marketing-api/catalog/reference/) and [Pinterest data-source guide](https://help.pinterest.com/en/business/article/before-you-get-started-with-catalogs). TikTok XML field compatibility remains unverified; test its sample in your catalog before scheduling it. No preset has been submitted to a provider as part of local SHO-1 testing.

Google no longer requires US sales-tax data; the preset does not map a tax column by default. Existing custom tax mappings are preserved for compatibility. Review optional destination fields against the provider's current specification before enabling a feed.
