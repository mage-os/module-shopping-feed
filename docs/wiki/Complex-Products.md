# Complex products

Configurable, grouped, and bundle products can produce parent rows, associated rows, or both. Decide the row model first, then configure inheritance, URLs, stock, price, and duplicate handling around it.

> Documentation baseline: release 1.2.5 (`v1.2.5`); earlier acceptance is identified by version. Last reviewed: 2026-11-09.

## Associated-product modes

Each complex product type offers these modes:

* **Only parent**, with no associated-product rows
* **Only associated products**, with no parent row
* **Both parent and associated products**

The correct choice depends on the recipient and the catalog. Test row counts and identifiers before applying one model to every product type.

**Feed stock selection in 1.2.0:** feed collections now apply the feed stock settings independently of the storefront setting that hides out-of-stock products. This includes simple, configurable, grouped, and bundle collections. All three complex-product modes and explicit stock inclusion/exclusion pass on Magento Open Source 2.4.8 and 2.4.9; see the repository's `docs/reviews/2026-10-03-local-acceptance.md`. Recipient validation still applies: a mode that adds a non-purchasable parent can produce rejected rows even when its children are valid.

## Configurable products

Available controls include:

* How associated products are added
* Whether out-of-stock children are allowed
* Whether a child inherits the parent's out-of-stock state
* Whether not-visible children receive unique deep links
* The separator used when attribute values are merged
* Per-column inheritance between parent and associated product; see [Inherit values from the parent](#inherit-values-from-the-parent)

Google Shopping defaults include item grouping and standard variant columns. Confirm that each child has a stable ID, the intended `item_group_id`, a working deep link, and the correct `color`, `size`, `material`, `pattern`, `gender`, and `age_group` values where applicable.

With default stock statuses and parent out-of-stock inheritance enabled, the module checks the parent's Magento salability before checking the child's stock. A configurable parent's own quantity can be zero while its children remain purchasable. Since 1.0.0, the module does not treat that zero parent quantity alone as out of stock. Custom stock attributes and disabled inheritance retain their configured behavior.

Google Local Inventory removes the unique-link and attribute-separator controls from the Configurable Products section, the unique-link control from Grouped Products, and Combined weight from Bundle Products. Its source-level rows focus on inventory context.

## Grouped products

Available controls include:

* How associated products are added
* Whether out-of-stock associated products are allowed
* Whether not-visible associated products receive unique URLs
* **Minimal price** or **Sum of associated products prices**
* Per-column inheritance between parent and associated product; see [Inherit values from the parent](#inherit-values-from-the-parent)

The summed price mode uses default associated quantities when they are available. If the grouped product has no usable default quantities, the mapper can fall back to minimal price.

## Inherit values from the parent

Associated-product rows of configurable and grouped products take every value from the child by default. When a child leaves an attribute empty that the parent fills, such as description, brand, a marketing text, or an image, **Value inheritance by column** fills that column of the child row from the parent.

### Add an inheritance rule

1. Open the feed in **Catalog > Mage-OS Shopping Feed > Feeds Management**. Confirm **Columns Map** contains the column, then **Save** the feed. The rule editor lists only columns in the saved map.
2. Open **Configurable Products**, or **Grouped Products** for grouped children, and find **Value inheritance by column**.
3. Add a row, select the **Column**, and choose **Map value from**:

| Map value from | Result for each child row |
| --- | --- |
| associated if exists, otherwise from parent | The child's value; when it is empty, the parent's value. Use this to fill gaps on children. |
| parent if exists, otherwise from associated | The parent's value; when it is empty, the child's value. |
| parent only | Always the parent's value, for example a shared description. |
| associated only | Always the child's value. Columns without a rule behave this way. |

4. Leave **Only for attributes** empty unless the column uses the Concatenate Attributes directive; see below.
5. Save, then run **Test Feed** with the parent SKU and compare the child rows. See [Testing one product](Testing-One-Product).

### What a rule covers

* A rule applies to any column, whether it maps an attribute or a directive. A rule on an image column falls back to the parent image; a rule on a URL column to the parent URL.
* Rules change associated-product rows only. A parent row, when the associated-products mode includes one, always uses the parent's own values.
* The child's **Replace empty values** rules run first. The parent is consulted only when the child value is still empty after them.
* Empty means empty to PHP: an empty string and the value `0` both count. Do not add a fallback rule to a quantity or other numeric column where zero is a real value.
* Price, sale price, availability, and quantity columns already use configurable- and grouped-specific directives that aggregate the children. A rule on those columns replaces that logic with the parent's own value, so add one only deliberately.

### Concatenate Attributes columns

For a column mapped to **Concatenate Attributes**, a rule without **Only for attributes** applies to the whole rendered pattern. To inherit only some placeholders, enter their attribute codes, comma separated, in **Only for attributes**. Each listed `{{code}}` placeholder is resolved with the rule and the rest of the pattern is rendered from the child.

### Bundle products

Bundle option rows have no inheritance rules in 1.2.5; the **Bundle Products** section does not offer them. Values a bundle recipient needs must be present on the option products themselves.

### Verify inheritance

* **Test Feed** with the parent SKU. Each child row must show the child's value where the child has one and the parent's value where it does not.
* Include a child whose value is literally `0` if the column is numeric.
* Generate the complete file and compare row counts with a run without the rule. Inheritance changes values, not the number of rows.

## Bundle products

Available controls include:

* How option products are added
* Whether shipping weight is calculated as the combined weight of selected items

Test fixed and dynamic price or weight bundles separately. Optional and required selections can change both the minimum viable row and its calculated values.

## Complex Product Context Prioritization

The **Complex Product Context Prioritization** setting in General Configuration (General in 1.2.0) can prioritize individually visible simple products in their complex-product context, including Search-only children. With it enabled, a child created before its parent retains the same parent grouping as a child created later. Enable it when standalone processing produces duplicate or contextually wrong rows, then measure the generation cost.

Version 1.2.4 resolves complex-parent lookup tables through Magento's resource connection, including an installation's table prefix, and handles configurable shipping weight when minimum-price product IDs are absent. Include both cases when validating customized or prefixed stores.

## Verification matrix

For each product type, record:

| Check | Parent | Associated or option item |
| --- | --- | --- |
| Included or excluded |  |  |
| ID |  |  |
| URL |  |  |
| Price and sale price |  |  |
| Availability and quantity |  |  |
| Image |  |  |
| Category and taxonomy |  |  |
| Inherited columns |  |  |

Run the matrix for in-stock and out-of-stock combinations. For MSI Local Inventory, repeat it across at least two sources linked to the selected website stock.
