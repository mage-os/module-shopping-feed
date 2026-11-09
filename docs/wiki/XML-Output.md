# XML Output

> Documentation baseline: unreleased SHO-1. Last reviewed: 2026-11-09.

Select **XML** in the feed's **General > Output Format** field and set **File Name** to a name ending in `.xml`. Existing feeds default to delimited output.

The XML settings are:

| Setting | Behavior |
| --- | --- |
| XML Header | XML declaration and opening document elements. Declare namespaces here. |
| XML Item Element | Element name wrapping each mapped product row, such as `product` or `item`. |
| XML Footer | Closing document elements matching the header. |
| XML Optional Columns | Comma-separated column names whose tags are omitted when their value is empty. `*` selects all columns. Zero is retained. |
| XML CDATA Columns | Comma-separated column names written as CDATA. `*` selects all columns. Other values use XML escaping. |

Columns Map names become XML element names. Use valid names such as `id`, `description`, or `g:id`; declare the `g` namespace in the header. Array values become repeated elements. Empty values produce empty elements unless the column is optional. The delimited format's Empty Cell Value, delimiter, and enclosure settings do not apply to XML.

Output uses XML 1.0 and UTF-8. The existing product text cleanup still applies before serialization. CDATA controls serialization, not whether product HTML is retained. Invalid element names, undeclared namespaces, malformed wrappers, invalid characters, DTDs, and entity declarations stop generation. The previously published feed remains in place when generation fails.

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

Also configure `default_feed_config/file/feed` with an `.xml` filename and map the destination's element names in Columns Map. This adds a general XML writer; destination-specific presets and certification are separate work. Nested per-item structures and XML attributes are not supported by this flat column mapping.
