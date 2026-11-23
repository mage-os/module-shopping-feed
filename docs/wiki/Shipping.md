# Shipping

The Shipping section configures the output produced by a column mapped to the **Shipping** directive. It does nothing unless that directive is present in Columns Map.

> Documentation baseline: 1.3.0 release candidate. Last reviewed: 2026-11-12.

Version 1.2.4 handles configurable products without a minimum-price product list and fixes the catalog tax helper used when the including-tax flag is omitted. Regenerate representative configurable weights and taxed shipping after upgrading; see [Release 1.2.4](Release-1-2-4).

## Settings

| Setting | Purpose |
| --- | --- |
| Methods | Selects eligible Magento shipping methods. |
| Countries | Selects the destination countries used for calculation. |
| Shipping Weight Column | Selects the saved feed column used as weight input. |
| Only Minimum Price | Emits only the lowest eligible shipping result. |
| Only Free Shipping | Emits shipping only when a free option is available. |
| Add Tax to Shipping Price | Includes tax in the calculated shipping price. |

## Carrier boundary

Feed generation skips the carriers on the feed type's real-time list (`carrier_realtime`): by default `ups`, `usps`, `fedex`, `dhl` and `dhlint`. This keeps a feed from sending a rate request to a carrier service for every product. Any other carrier selected in Shipping Methods is asked for rates for every product and destination, including a third-party carrier that fetches live rates.

Select only table-based or flat-rate methods for a feed. If a custom carrier calls a live rate service, leave it unselected, or add its carrier code to `carrier_realtime` for that feed type in your own module's `etc/mageos_shopping_feed.xml`. The value replaces the default list, so keep the five default codes in it. Configure carrier-calculated shipping in the recipient platform when the feed cannot safely calculate it.

Select only the countries needed by the data source. Every additional destination can increase processing work and output size.

## Set up the column

1. Add a `shipping` column in Columns Map.
2. Map it to the **Shipping** directive.
3. Save the feed.
4. Return to Shipping and select methods, countries, and a saved weight column.
5. Test products across weight, price, free-shipping, and tax thresholds.

## Google configuration

Shipping requirements depend on target country and account configuration. Use the module to generate only values you can verify against Magento. Confirm the current account-level and item-level requirements in Google's product data documentation.

## Performance

Shipping is one of the more expensive directives because it can evaluate several methods and destinations for every product. If a large feed slows down, compare a test run with the Shipping directive removed before changing batch size or infrastructure.

## XML shipping (1.3.0)

Google Shopping, Microsoft, Pinterest, and Meta offer the built-in Shipping directive in Columns Map, and their XML presets turn its output into nested shipping elements. Structured mapper values preserve commas and colons in service names. Manually mapped shipping strings use `country:region:service:price`, with comma-separated groups; avoid those separators inside manual subfield values. For example, `DE::Standard:6.49 EUR` supplies country, service, and price while omitting region. Malformed groups skip the row with a log message. TikTok retains packed shipping values. See [XML Output](XML-Output#preset-configuration) and review shipping mappings when switching formats.
