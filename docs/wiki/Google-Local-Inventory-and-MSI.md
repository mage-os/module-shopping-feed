# Google Local Inventory and MSI

Google Local Inventory output connects a product ID with store-level availability, quantity, and price. With Magento Multi-Source Inventory enabled, the module can produce source-specific rows for sources linked to the selected website stock.

> Documentation baseline: released behavior plus unreleased SHO-1, as marked below. Last reviewed: 2026-11-10.

Version 1.2.3 includes the source and reservation corrections below. The [Mage-OS 3.5.0 hardening report](https://github.com/mage-os-lab/module-shopping-feed/blob/v1.2.3/docs/reviews/2026-10-08-hardening-and-mageos-3.5-verification.md) records native quantities, website-stock isolation, compensation, disabled sources, and generated Local Inventory rows. Versions through 1.2.2 deducted reservations per source; review changed quantities after upgrading.

Version 1.2.4 additionally reads source codes through `SourceItemInterface::getSourceCode()`, so interface-only source-item implementations work without array access. The [Mage-OS 3.6 verification](Release-1-2-4) includes enabled, disabled, and unlinked physical-source coverage.

## Prerequisites

* A working Google Shopping product feed whose product IDs align with Local Inventory IDs
* A non-serving Local Inventory test destination
* Magento Inventory APIs for source-level rows
* Sources linked to the stock resolved for the selected feed website
* A confirmed mapping between Magento source codes and Google store codes

Review Google's current [Local inventory data specification](https://support.google.com/merchants/answer/14819809?hl=en) before configuring the destination.

## Default output

The Local Inventory template starts with:

* `store_code`
* `id`
* `availability`
* `price`
* `sale_price`
* `sale_price_effective_date`
* `quantity`

UTF-8 and a tab delimiter are configured by default.

## Map sources to store codes

Open **Categories Map** and enter one mapping per line under **Inventory Source to Google Store Code**:

```text
warehouse_indy=INDIANAPOLIS-01
warehouse_chicago=CHICAGO-01
```

Unmapped sources use the Magento source code. That fallback is deterministic, but it is only correct when Google uses the same value.

## Source selection

When default stock handling and MSI are active, the module:

1. Resolves the stock for the selected website.
2. Finds enabled sources linked to that stock.
3. Loads source items for the current SKU. In version 1.2.0, configurable parent rows use distinct linked sources from their children; the parent does not need its own source item.
4. Produces source-specific values for store code, quantity, and availability.

Sources outside the selected website stock should not produce rows.

Without source-level MSI context, the mapper falls back to a `default` or `custom` source label and the normal product inventory path. Do not treat that fallback as proof of a valid Local Inventory implementation.

## Local availability

Online backorders do not establish stock in a physical store. Source rows report `in_stock` only when the source and source item are enabled and the physical source quantity is positive; otherwise they report `out_of_stock`. Online backorder settings and disabled stock management do not override an empty or disabled source. Without source context, an online `backorder` or `preorder` result becomes `out_of_stock`.

Configurable and grouped parent rows continue to use associated-product quantities; an online parent backorder flag no longer overrides their local quantity. The configurable-parent correction in 1.2.0 also requires an enabled child source item at the same source before reporting the parent in stock. Test parent and child modes against your physical inventory before enabling uploads.

Google also accepts `limited_availability` and `on_display_to_order`. The default mapper does not infer display-to-order eligibility. Google can classify an available quantity of one or two as limited availability when quantity is supplied. Use a deliberate custom mapping if your store needs another supported local status; do not send online `backorder` or `preorder` values. [Accepted availability values](https://support.google.com/merchants/answer/14819809?hl=en).

## Reservations

MSI reservations belong to a website stock and do not identify a physical source. Source-specific Local Inventory rows therefore report physical quantities without deducting the website reservation from each source.

**Use Stock Reservations** applies to aggregate inventory quantities, including the fallback when there is no source context. The module adds the reservation total for the feed website's stock once after summing eligible sources, and clamps a negative result to zero. The existing Local Inventory default remains enabled, but does not change physical-source rows.

Record the physical source quantity, reservation total, expected feed quantity, and expected availability for each test SKU. Reservation behavior must be proven with real fixtures before production use.

## Complex products

When **Inherit parent out-of-stock** is enabled for configurable children using default stock, the parent is checked for salability rather than positive parent quantity. Configurable parents normally have zero legacy quantity; that alone no longer marks in-stock children out of stock. Each child still needs available local stock. Custom availability attributes retain their configured behavior.

The configurable, grouped, and bundle modes still control whether parent rows, associated rows, or both are emitted. Local Inventory preserves the source context while mapping associated items.

Test all three configurable modes when they are relevant:

* Parent only
* Associated products only
* Parent and associated products

Confirm that each intended SKU and store code pair appears once, with no source row removed by normal duplicate-product handling.

## Verification

Use at least two enabled sources and one reservation. Confirm:

* Each source is linked to the selected website stock.
* Every source maps to the intended Google store code.
* Quantity and availability come from the same source context.
* A stock reservation changes the aggregate quantity once and leaves each physical-source quantity unchanged.
* Disabled physical sources do not produce rows.
* Product IDs match the primary Google Shopping feed.
* A SKU/store-code pair is not duplicated.
* Merchant Center accepts a non-serving test file.

The earlier nine controlled simple-product scenarios are recorded in `docs/reviews/2026-10-02-docker-operations-acceptance.md`. Candidate `133af71` adds fifteen native configurable MSI scenarios per Magento Open Source 2.4.8/2.4.9 profile: parent-only, child-only, and combined modes across baseline, reservation, compensation, source-item disablement, and restoration. Native stock APIs and indexers establish the custom stock. Quantities, availability, source filtering, and duplicate protection pass. See `docs/reviews/2026-10-03-local-acceptance.md` for the exact data, corrections, and cleanup. Checkout-order placement, asynchronous consumers, and recipient ingestion remain separate checks.

## XML and optional pickup data (unreleased SHO-1)

Select **XML** in **General > Output Format** and use an `.xml` file name. The Local Inventory preset supplies the RSS structure. Default inventory mappings do not enable pickup or infer a store's pickup policy.

For pickup-enabled products, add `pickup_sla` and any applicable `pickup_method` in **Columns Map** using values that match your store's service. Map `pickup_cost` as `flat_rate:free_threshold`, including currencies, for example `3 EUR:20 EUR`. Use `3 EUR:` when there is no free threshold. XML automatically nests these as `g:pickup_cost_flat_rate` and `g:pickup_cost_free_threshold` inside `g:pickup_cost`; blank subfields are omitted.

Google's pickup-cost requirement applies to pickup-enabled products in the UK, EU, and EFTA when the website charges pickup fees, rather than every Local Inventory feed. Check the [current Local Inventory specification](https://support.google.com/merchants/answer/14819809?hl=en-GB) and [pickup-cost attribute](https://support.google.com/merchants/answer/16988704?hl=en). The XML structure does not supply or validate the merchant's pickup policy.

## Automatic XML preset (unreleased SHO-1)

In **General**, select **Output Format: XML** and set **File Name** to a name ending in `.xml`. This channel supplies its RSS header, item wrapper, footer, and namespace rules automatically; no manual XML structure is required. Existing default mappings remain usable. Review custom compound fields when switching formats, and inspect a generated sample before changing the destination's data-source URL and format. Empty optional tags are omitted. See [XML Output](XML-Output) for setup, field conversions, and validation limits. Delimited output remains the default.
