# Quick start

The safest first feed has one store view, no upload destination, no schedule, and a small set of known products. Generate it, inspect it, correct it, then add automation.

> Documentation baseline: released behavior plus unreleased SHO-2 and SHO-3, as marked below. Last reviewed: 2026-11-10.

Read [Admin UI Component forms](Admin-UI-Component-Forms) for the 1.2.0 editor. Its collapsible sections replace the old tabs; category generation and promotion-date preservation pass the recorded acceptance checks. Start with disabled test feeds before enabling delivery.

## 1. Open feed management

In the Admin, go to **Catalog > Mage-OS Shopping Feed > Feeds Management**.

## 2. Create a feed

Choose one of the available templates:

* **Generic** for a custom delimited product feed
* **Google Shopping** for a Google product data source
* **Google Local Inventory** for location-specific inventory data

Google Promotions are configured inside a Google Shopping feed rather than created as a separate New Feed preset. Release 1.2.0 and later also include Meta, Microsoft, TikTok, Pinterest, and OpenAI Google-compatible presets; see [Feed types and lifecycle](Feed-Types-and-Lifecycle).

## 3. Set the feed identity

On **General Configuration** in 1.1, or **General** in the UI Component editor:

1. Enter a clear Admin name.
2. Select the store view.
3. Select the feed currency.
4. Keep the output under `pub/media/mageos-shopping-feed`.
5. Review delimiter and stock behavior.
6. Open **Run Schedule** in 1.1, or **Schedule** in 1.2.0, and remove any default schedule before the first save. New feeds can start with a daily schedule.
7. Confirm **Uploads** is empty, then save before configuring fields that depend on the current column map.

## 4. Review the default columns

Open **Columns Map**. A template supplies defaults, but they are only a starting point. Confirm the source for identifiers, URLs, prices, availability, images, taxonomy, and variant data against the target store.

For Google Shopping, map real MPN/GTIN attributes and an expected shipping date when backorders or preorders are possible. Confirmed identifier absence is an explicit choice, not a substitute for missing catalog data. For Generic comma feeds, check that the recipient parses quoted CSV. See [Google Shopping](Google-Shopping) and [Generic feeds](Generic-Feeds).

## 5. Limit the first test

Use a known SKU that represents the catalog behavior you need to prove. Good fixtures include:

* A simple product at regular price
* A simple product with an active sale price
* An out-of-stock product
* A configurable product with at least two variants
* An MSI product assigned to more than one source, for Local Inventory

Use **Test Feed** from the feed grid with **Generate Feed** permission (required in 1.2.4), or run:

```bash
bin/magento mage-os:shopping-feed:generate <feed_id> <sku>
```

Compare each output value with the Admin product, store view, price, category, URL, and inventory state.

## 6. Generate the file

Use **Run Now** to add the feed to the queue. Magento cron processes queued work. Follow status changes in the grid and inspect **View Log** if processing fails.

For a direct CLI run of one feed:

```bash
bin/magento mage-os:shopping-feed:generate <feed_id>
```

## 7. Inspect before automating

Check:

* Header names and column order
* Row column counts
* Product count and skipped count
* IDs, URLs, prices, sale dates, availability, and images
* Configurable parent and child behavior
* Delimiter handling for quotes, tabs, commas, line breaks, and Unicode
* The file location and public URL, if one is shown

Only after the file is accepted should you add [schedules](Scheduling-and-Queues) or [upload destinations](Uploads).

## Optional attribute rules (unreleased SHO-2 and SHO-3)

To choose a column value from a condition, use [Conditional Value](Conditional-Values). To limit the exported products to an attribute rule, use [Product Selection](Product-Selection). Both are available in all eight feed types on this branch, not in 1.2.5. Save and reopen the rules, test one matching and one nonmatching product, then inspect a full generated file. Include parent and child fixtures when using parent scope.
