# Product selection

> Documentation baseline: 1.3.0 release candidate. Last reviewed: 2026-11-12.

**Product Filters > Product selection conditions** restricts a feed to products matching attribute rules. It is available for all eight feed types. Existing feeds have no conditions and retain their existing eligibility rules. Use [Conditional Value](Conditional-Values) to change a column's value instead of excluding a product.

## Select a campaign's products

For example, include products with a SKU starting with `SALE-` and a configured base image:

1. Open **Catalog > Mage-OS Shopping Feed > Feeds Management** and configure a test feed with no schedule or uploads.
2. Open **Product Filters > Product selection conditions** and choose **All conditions**.
3. Add a **Product** condition: **SKU**, **matches wildcard**, `SALE-*`.
4. Add a **Product** condition: **Has base image**, equality, **Yes**.
5. Save and reopen the feed to confirm both conditions.
6. Test a matching SKU with an image, a matching SKU without an image, and a SKU outside the prefix. Only the first should pass this selection rule. Other feed filters may still exclude it.
7. Generate the full test file, inspect included and excluded SKUs, and review **View Log** before scheduling delivery.

Choose **Any condition** if matching either condition should be enough. Removing every condition disables this additional filter. There is one All/Any group with at most 50 conditions; nested groups are not supported.

## Condition values and operators

The editor offers operators appropriate to each attribute. Attributes do not need **Use for Promo Rule Conditions** enabled. Structured attributes such as media-gallery or tier-price data are not supported as conditions.

| Data | How it is compared |
| --- | --- |
| Product attributes | Values in the feed's selected store view, before column mapping and output transformations |
| Price and other numbers | Raw stored values, before currency conversion, tax formatting, or price directives |
| Dropdowns and multiselects | Stored option IDs, not translated labels; use the editor's choices |
| List comparisons | Comma-separated raw values or IDs, including category IDs |
| Date and Date and Time | `YYYY-MM-DD`, comparing the stored calendar day and ignoring time; no timezone conversion |
| Has base image | Yes when `image` is neither blank nor `no_selection`; no file-existence, gallery, or inherited-image check |
| SKU wildcards | Case-sensitive, whole-SKU match; `*` matches zero or more characters and `?` matches one character, including Unicode characters |

A missing (`null`) attribute matches only **is undefined**, not an ordinary negative comparison. The condition engine does not reload a default-store product to supply a missing value. A missing parent still fails every parent-scoped condition, including is undefined.

For example, `SALE-*` matches `SALE-BLUE` but not `sale-blue`; `*-BLUE` matches a suffix. Other punctuation is literal, not a regular expression. A stored date of `2026-03-01 15:30:00` matches the date `2026-03-01`. Numeric-looking strings such as `00123` remain distinct in saved configuration from `123`.

## Parent and child behavior

Each condition has **Product** or **Parent product** scope. For an associated row, parent conditions read the parent currently being mapped. For a standalone row, Product Selection searches direct configurable, grouped, and bundle parents assigned to the feed website, including disabled parents.

With multiple parents, the complete rule must match against at least one parent. Two parent conditions cannot be satisfied by two different parents. For example, an All rule requiring parent status Enabled and parent attribute set Clothing fails if one parent is enabled in another attribute set and the Clothing parent is disabled.

A product without a parent never satisfies a parent condition, including a negative or undefined comparison. A product-only alternative in an Any group can still match. Conditional Value has different standalone-parent behavior: it uses only the active mapping parent and does not search catalog parents.

Selection evaluates parent and associated output products separately. A rejected parent does not automatically reject its matching children. Existing type, stock, category, visibility, status, per-product exclusion, and column filters still apply. A condition can narrow eligibility but cannot restore a product excluded by those filters. In particular, a visibility rule does not make a hidden standalone product eligible. Review [Complex products](Complex-Products) when configuring variant feeds.

## Logs, performance, and recovery

Selection runs before duplicate tracking. Its skip count describes rejected output candidates identified by product and parent context, not distinct SKUs or the number of final inventory/custom-option rows. Repeated checks of one candidate count once; a standalone candidate and a parent-associated candidate count separately. The count can exceed scanned products. Logs identify the parent context where present.

Conditions are evaluated after products are loaded. They reduce exported rows, not the catalog scan size. Parent lookups use a bounded store-scoped cache reset for each run or batch. Measure full generation with the intended rules and catalog before changing production schedules.

Invalid saved or imported conditions fail validation before generation processes products, including an empty catalog. Check unknown attributes, option IDs, date formats, unsupported operators, and malformed rule structure. A queued feed with invalid configuration can continue retrying until corrected; inspect its log, repair and save the rule, then use the normal [queue recovery procedure](Scheduling-and-Queues). Do not assume a failed run published a new file.

The editor retains legacy keyed condition rows for repair and saves them as a list after editing. Programmatic `null` clears selection and persists as an empty list. Developers should follow the structured parameter contract in repository `docs/ui-component-editor.md`.

Local functional, persistence, Admin, boundary, and batching evidence is recorded in `docs/reviews/2026-11-10-sho-3-independent-fixes.md`. Test both accepted and rejected products in the target store; those results do not establish production capacity or destination acceptance.
