# Generic feeds

Use a Generic feed when the recipient needs a custom product file without a bundled destination template. Delimited output is the default; unreleased SHO-1 also supports manually configured XML.

> Documentation baseline: released behavior plus unreleased SHO-1, as marked below. Last reviewed: 2026-11-10.

## Starting schema

The Generic template starts with common product fields including ID, title, description, URL, prices, images, availability, quantity, SKU, options, sale dates, shipping weight, and review data.

Every column can be renamed, reordered, removed, or mapped to another product attribute or directive. Treat the defaults as a working example, not a recipient specification.

## Output format

The default output is UTF-8 and tab-delimited:

```text
pub/media/mageos-shopping-feed/mageos_shopping_feed_<feed_id>.txt
```

The Admin permits a supported delimiter and a safe filename under `pub/media/mageos-shopping-feed`. The path cannot escape that directory, and filenames are restricted to approved characters and `.txt`, `.csv`, `.tsv`, or `.xml` extensions.

Choose **Comma** for CSV output. With the enclosure controls left blank, the writer encloses every header and value in double quotes and doubles embedded quotes. Commas in source text, including decoded HTML entities, are preserved. For example, the value `"Quoted" title, café` becomes `"""Quoted"" title, café"` in the file. A trailing empty field is written as `""`.

This applies to both new and existing Generic feeds using a comma delimiter, including **Other** set to a comma. The filename extension does not select the serializer. Recipients must parse CSV rather than split each line on commas. Existing integrations that relied on stripped commas or unquoted values must be checked before resuming uploads.

The General section also provides **Cell Enclosure**, **Enclosure Escape**, and **Empty Cell Value**. An explicit enclosure overrides the comma default and also works with other delimiters. Leave Escape blank to double embedded enclosure characters, or enter the escape prefix required by the recipient. Empty Cell Value replaces empty cells; zero remains zero. Headers and values use the same enclosure rules.

For example, pipe-delimited output with `"` as its enclosure writes `A|B` as `"A|B"`, preserving the pipe inside the value. Without an enclosure, embedded tab/custom delimiters become spaces as before. HTML tags and line breaks are cleaned in all formats. This is a product-text export, not a lossless copy of HTML or multiline content.

Google-only column additions, identifier rules, backorder-date checks, and sale-price suppression do not apply to Generic output. Custom column names and order remain under your control.

Changing the extension does not create a recipient-specific XML schema. The generator remains driven by Columns Map and output parameters. Confirm the actual file structure expected by the recipient.

## Recommended setup

1. Obtain the recipient's current field specification and a valid sample file.
2. Set the store view, currency, and delimiter.
3. Reduce Columns Map to the required fields first.
4. Map stable identifiers, URL, price, availability, and images.
5. Add optional fields and transformations after the base file parses.
6. Use required-field filters for columns that cannot be empty.

## Useful directives

Generic feeds can use static values, concatenated attributes, category paths, category-defined product types, custom options, review values, expiration dates, shipping, and complex-product inheritance. See [Columns and directives](Columns-and-Directives).

## Verification

Parse the complete file using the recipient's delimiter and encoding rules. Confirm:

* The header and every row have the same number of columns.
* Embedded delimiters, quotes, HTML, line breaks, and Unicode do not corrupt rows.
* Product count matches the intended filters.
* Complex products follow the chosen parent and associated-product modes.
* The recipient accepts a non-production upload before scheduling begins.

## Manual XML (unreleased SHO-1)

Choose **General > Output Format: XML**, set a `.xml` filename, and configure the document header, item element, footer, optional columns, and CDATA columns. Columns Map names become element names. The default wrapper is `products` with one `product` per mapped row. See [XML Output](XML-Output#configure-a-generic-xml-feed) for a complete example, namespace rules, and validation. If a bundled channel meets your needs, use that channel's automatic preset instead.
