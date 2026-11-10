# Conditional values

> Documentation baseline: unreleased SHO-2 and shared SHO-3 condition controls; not included in 1.2.5. Last reviewed: 2026-11-10.

**Conditional Value** chooses what to write in one feed column. It is available in **Columns Map** and **Replace Empty Values** for all eight feed types. Use [Product Selection](Product-Selection) when you want to exclude entire products instead.

## Create a conditional column

For example, label products whose stored price exceeds 100 as `premium`, and other products as `standard`:

1. Open **Catalog > Mage-OS Shopping Feed > Feeds Management** and configure your feed. Start with a test feed without a schedule or uploads.
2. In **Columns Map**, add a row named `custom_label_0`, or edit that column if it already exists. New rows default to **Static Value**; explicitly select **Conditional Value** as the source.
3. Choose **All conditions**. Add a condition with attribute **Price**, operator greater than, and value `100`.
4. Set **If matching** to **Fixed text** with value `premium`.
5. Set **Otherwise** to **Fixed text** with value `standard`.
6. Save and reopen the feed. Confirm the condition and both outputs were retained.
7. Use **Test Feed** with products priced at 150, 100, and 50. Expect `premium`, `standard`, and `standard`, respectively, if those products pass the feed's other filters. Generate a complete test file and check the column before scheduling delivery.

The comparison reads the stored product attribute in the feed's store view. It does not compare the formatted price column, converted currency, or a previously calculated sale price. Choose a column supported by your destination.

## Choose the two outputs

| Output | What to enter | Result |
| --- | --- | --- |
| Fixed text | Literal text, `0`, or an empty value | That value, followed by the column's normal cleanup and transformations |
| Product attribute | An attribute such as `manufacturer` | Its value for the product being mapped; dropdowns and multiselects use store-view labels |
| Percentage adjustment | A numeric attribute and a percentage | Raw attribute value multiplied by `1 + percentage / 100`, formatted to two decimal places |

For a percentage adjustment, `price` of `100` with `-10` produces `90.00`; `10` produces `110.00`. Percentages must be between `-100` and `100000`. An empty source returns an empty value; a nonnumeric source fails generation. This calculation does not add a currency suffix, convert currencies, apply tax, or run the Price directive's sale-price logic. Check the destination's required price representation before using it in a price column.

Configure both outputs even if you currently expect every product to take the same branch. Both are validated on save and generation. Normal find-and-replace, text cleanup, output limits, and empty-value replacement still apply. Returning an empty value can therefore trigger a replacement or a required-field filter.

In **Replace Empty Values**, add Conditional Value as a replacement source for a column. It runs when that column needs a replacement, following the existing replacement order. It does not overwrite an already populated column.

## Conditions and parent products

A rule has one **All conditions** or **Any condition** group containing 1 to 50 conditions. Operators depend on the selected attribute. See [condition values and operators](Product-Selection#condition-values-and-operators) for option IDs, dates, image checks, and SKU wildcards shared by both features.

Conditions created in the Conditional Value editor read the product currently being mapped. The Product/Parent product scope selector is shown only in Product Selection. Existing [parent-value inheritance](Complex-Products) still controls whether a column is evaluated on the child or parent.

Programmatic Conditional Value rules can also carry `scope: parent`, which is preserved but not exposed as a selector in this editor. Such conditions read only the active mapping parent. Unlike Product Selection, they do not search catalog parents for a standalone row. Without an active parent, every parent condition is false, including negative and undefined comparisons; an Any group can still match a product-scoped condition. Condition scope does not redirect the output attribute to the parent. Outputs read the product being mapped. Test a standalone product, a parent, and its children when combining rules and inheritance.

There are no nested groups, ordered rule chains, arbitrary formulas, or references to other generated columns. To remove the rule, change the source to another directive, such as Static Value or a product attribute. Leaving a Conditional Value rule without conditions is invalid.

## Troubleshooting and verification

If saving fails, check that the selected attributes still exist, the operators match their attribute types, dates use `YYYY-MM-DD`, and both outputs are complete. Imported rules receive the same server-side validation. A malformed rule or nonnumeric percentage source is an error, not a request to use Otherwise.

If a value is unexpected, check the selected store view, raw source attribute, active parent, and subsequent column transformations. If the whole product is missing, review Product Filters, required fields, and **View Log**. Keep one matching and one nonmatching product in your acceptance checks, including blank and zero values where relevant.

Local evidence is recorded in the repository under `docs/reviews/2026-11-10-sho-2-editor-switch.md` and `docs/reviews/2026-11-10-sho-2-default-mapping.md`. Existing-row switching, save/reopen, and generated values were tested in Admin. The later default-row change has unit and persistence coverage; its browser save/reopen follow-up remains incomplete. These records do not establish destination ingestion acceptance.
