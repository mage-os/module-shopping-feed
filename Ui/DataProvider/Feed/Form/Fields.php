<?php

declare(strict_types=1);

namespace MageOS\ShoppingFeed\Ui\DataProvider\Feed\Form;

use MageOS\ShoppingFeed\Model\Feed;

/** Standard Magento fields, shared by the editor metadata and its safe data projection. */
class Fields
{
    /**
     * Constructor
     *
     * @param Options $options
     */
    public function __construct(private Options $options)
    {
    }

    /**
     * Get the standard editor fields of the feed, grouped by form section.
     *
     * @param Feed $model
     * @return array
     */
    public function get(Feed $model): array
    {
        $sections = [
            'general' => [
                'name' => ['formElement' => 'input'] + [
                    'label' => __('Name'),
                    'required' => true,
                    'notice' => __('The name of the Feed'),
                ],
                'general_currency' => ['formElement' => 'select'] + [
                    'label' => __('Feed Currency'),
                    'required' => true,
                    'options' => $this->options->get('AvailableCurrencies'),
                    // phpcs:ignore Generic.Files.LineLength.TooLong -- translatable phrase
                    'notice' => __('This lists only allowed currencies on the store view. WARNING: Changing to a currency which is not displayed on frontend can lead to feed being rejected with provider! Don\'t forget to update rates when adding new currency to the store.'),
                ],
                'general_feed_dir' => ['formElement' => 'input'] + [
                    'label' => __('Feed Path'),
                    'required' => true,
                    'notice' => __('It\'s the dir path to save the feed. Assure write permissions.'),
                ],
                'output_params_delimiter' => ['formElement' => 'select'] + [
                    'label' => __('Delimiter'),
                    'required' => true,
                    'options' => $this->options->get('Delimiter'),
                ],
                'general_apply_catalog_price_rules' => ['formElement' => 'select'] + [
                    'label' => __('Apply Catalog Price Rules'),
                    'required' => true,
                    'options' => $this->options->get('Yesno'),
                    'notice' => __('It will apply catalog price rules when computing the Sale Price.'),
                ],
                'general_use_default_stock' => ['formElement' => 'select'] + [
                    'label' => __('Use default Stock Statuses'),
                    'required' => true,
                    'options' => $this->options->get('Yesno'),
                    'notice' => __('If your store is using a custom attribute for stock status, change this to No.'),
                ],
                'general_stock_attribute_code' => ['formElement' => 'select'] + [
                    'label' => __('Alternate Stock/Availability Attribute'),
                    'required' => false,
                    'options' => $this->options->get('Attributes'),
                    'notice' => match ($model->getType()) {
                        // phpcs:ignore Generic.Files.LineLength.TooLong -- translatable phrase
                        'meta_catalog' => __('Use in_stock, out_of_stock, backorder, or preorder. Meta product feeds support in stock and out of stock. Backorders and preorders export as out of stock until available.'),
                        // phpcs:ignore Generic.Files.LineLength.TooLong -- translatable phrase
                        'microsoft_merchant_center' => __('Use in_stock, out_of_stock, backorder, or preorder. Microsoft supports in stock, out of stock, and preorder. Backorders export as out of stock until available.'),
                        // phpcs:ignore Generic.Files.LineLength.TooLong -- translatable phrase
                        'tiktok_catalog' => __('Use in_stock, out_of_stock, backorder, or preorder. TikTok exports these as in stock, out of stock, available for order, and preorder respectively.'),
                        // phpcs:ignore Generic.Files.LineLength.TooLong -- translatable phrase
                        'openai_google_compatible' => __('Use in_stock, out_of_stock, preorder, or backorder. Map a real availability_date for preorder and backorder. The OpenAI native spelling pre_order is not accepted by this profile.'),
                        // phpcs:ignore Generic.Files.LineLength.TooLong -- translatable phrase
                        'pinterest_catalog' => __('Use in_stock, out_of_stock, backorder, or preorder. Pinterest supports in stock, out of stock, and preorder. Backorders export as out of stock until available.'),
                        // phpcs:ignore Generic.Files.LineLength.TooLong -- translatable phrase
                        default => __('To fill \'availability\'. The attribute\'s values can be: \'in stock\', \'available for order\', \'out of stock\', \'preorder\'. Other values will be replaced by \'out of stock\'.'),
                    },
                ],
                'general_use_qty_increments' => ['formElement' => 'select'] + [
                    'label' => __('Use Qty Increments'),
                    'required' => true,
                    'options' => $this->options->get('Yesno'),
                    'notice' => __('When computing product prices, use qty increments to multiply unit price'),
                ],
                'general_use_stock_reservations' => ['formElement' => 'select'] + [
                    'label' => __('Use Stock Reservations'),
                    'required' => true,
                    'options' => $this->options->get('Yesno'),
                    'notice' => __('Consider stock reservations when computing stock quantitys and availability'),
                ],
                'general_complex_duplicates_check' => ['formElement' => 'select'] + [
                    'label' => __('Complex Product Context Prioritization'),
                    'required' => true,
                    'options' => $this->options->get('Yesno'),
                    // phpcs:ignore Generic.Files.LineLength.TooLong -- translatable phrase
                    'notice' => __('Simple products "visible in catalog" are been prioritized to be processed in context of complex products if they are attached to configurable, grouped or bundle. It may slow down processing.'),
                ],
            ],
            'categories' => [
                'categories_locale' => ['formElement' => 'select'] + [
                    'label' => __('Feed Localization'),
                    'required' => true,
                    'options' => $this->options->get('Locale'),
                    // phpcs:ignore Generic.Files.LineLength.TooLong -- translatable phrase
                    'notice' => __('Changing the language of your feed affects how Apparels are matched using Google taxonomies. Your products should also be in the same language. Refer to \'Google Category of the Item\' attribute. This setting does not affect price formatting, assure proper store language for that.'),
                ],
                'categories_include_all_products' => ['formElement' => 'select'] + [
                    'label' => __('Include products w/o category'),
                    'required' => true,
                    'options' => $this->options->get('Yesno'),
                    // phpcs:ignore Generic.Files.LineLength.TooLong -- translatable phrase
                    'notice' => __('If enabled, products that do not belog to a category will be added to the feed along with ones from disabled categories. Note that the taxonomy will be missing in this case, so you can capture that in a Replace Empty rule under Filters.'),
                ],
                'categories_sort_mode' => ['formElement' => 'select'] + [
                    'label' => __('Categories priority mode'),
                    'required' => true,
                    'options' => $this->options->get('PriorityMode'),
                    // phpcs:ignore Generic.Files.LineLength.TooLong -- translatable phrase
                    'notice' => __('If set to use priority of categories of the same level, deeper level categories are mateched first, than apply the priority of categories at the same level to detemine which one will be matched for a product with multiple categories.'),
                ],
            ],
            'filters' => [
                'filters_add_out_of_stock' => ['formElement' => 'select'] + [
                    'label' => __('Allow Out of Stock'),
                    'required' => true,
                    'options' => $this->options->get('Yesno'),
                ],
                'filters_product_types' => ['formElement' => 'multiselect'] + [
                    'label' => __('Submit only products of these types'),
                    'required' => true,
                    'options' => $this->options->get('Types'),
                    // phpcs:ignore Generic.Files.LineLength.TooLong -- translatable phrase
                    'notice' => __('Products submitted to the feed have to be visible in Catalog, meaning visibility "Catalog", "Search", "Catalog, Search", but also "Not Visible Individually if they are part of a visible configurable".'),
                ],
                'filters_attribute_sets' => ['formElement' => 'multiselect'] + [
                    'label' => __('Submit only products that have these attribute sets'),
                    'required' => true,
                    'options' => $this->options->get('AttributeSets'),
                ],
                'filters_skip_column_empty' => ['formElement' => 'multiselect'] + [
                    'label' => __('Skip Products with empty'),
                    'required' => true,
                    'options' => $this->options->get('Columns'),
                    // phpcs:ignore Generic.Files.LineLength.TooLong -- translatable phrase
                    'notice' => __('Avoid having empty values for your items in the feed. Columns must exist in Columns Map, save your config before looking for columns here.')
                ],
            ],
            'options' => [
                'options_mode' => ['formElement' => 'select'] + [
                    'label' => __('How to add product options'),
                    'required' => true,
                    'options' => $this->options->get('OptionHandling'),
                    'notice' => __('Detail product options into one single row or multiple rows, one for each option.'),
                ],
                'options_vary_categories' => ['formElement' => 'multiselect'] + [
                    'label' => __('Multiple rows only for products in these categories'),
                    'required' => false,
                    'options' => $this->options->categories($model),
                ],
            ],
            'configurable' => [
                'configurable_associated_products_mode' => ['formElement' => 'select'] + [
                    'label' => __('How to add associated products'),
                    'required' => true,
                    'options' => $this->options->get('AssociatedMode'),
                    // phpcs:ignore Generic.Files.LineLength.TooLong -- translatable phrase
                    'notice' => __('Associated products can be added in the feed as separate items even if they are not visible in catalog.'),
                ],
                'configurable_add_out_of_stock' => ['formElement' => 'select'] + [
                    'label' => __('Allow Out of Stock'),
                    'required' => true,
                    'options' => $this->options->get('Yesno'),
                    'notice' => __('For associated products of configurable products.'),
                ],
                'configurable_inherit_parent_out_of_stock' => ['formElement' => 'select'] + [
                    'label' => __('Inherit parent Out of Stock status'),
                    'required' => true,
                    'options' => $this->options->get('Yesno'),
                    'notice' => __(
                        'Forces "Out of Stock" for all sub-items when the configurable item is Out of Stock.'
                    ),
                ],
                'configurable_associated_products_link_add_unique' => ['formElement' => 'select'] + [
                    'label' => __('Unique urls for associated products not visible'),
                    'required' => true,
                    'options' => $this->options->get('Yesno'),
                    // phpcs:ignore Generic.Files.LineLength.TooLong -- translatable phrase
                    'notice' => __('The new unique url will be formed from configurable product url and the option ids as parameters. e.g http://example.com/configurable.html?option_1=x&option2=y'),
                ],
                'configurable_attribute_merge_value_separator' => ['formElement' => 'input'] + [
                    'label' => __('Associated Product Attribute Value Separator'),
                    'required' => false,
                    // phpcs:ignore Generic.Files.LineLength.TooLong -- translatable phrase
                    'notice' => __('Variant attributes values like color and size, defined above, are been merged together for each item using the separator defined here.'),
                ],
            ],
            'grouped' => [
                'grouped_associated_products_mode' => ['formElement' => 'select'] + [
                    'label' => __('How to add associated products'),
                    'required' => true,
                    'options' => $this->options->get('AssociatedMode'),
                ],
                'grouped_add_out_of_stock' => ['formElement' => 'select'] + [
                    'label' => __('Allow Out of Stock'),
                    'required' => true,
                    'options' => $this->options->get('Yesno'),
                    'notice' => __('For associated products of configurable products.'),
                ],
                'grouped_associated_products_link_add_unique' => ['formElement' => 'select'] + [
                    'label' => __('Unique urls for associated products not visible'),
                    'required' => true,
                    'options' => $this->options->get('Yesno'),
                    // phpcs:ignore Generic.Files.LineLength.TooLong -- translatable phrase
                    'notice' => __('The new unique url will be formed from grouped product url and the ids of the associated product. I.e http://example.com/grouped.html?prod_id=123'),
                ],
                'grouped_price_display_mode' => ['formElement' => 'select'] + [
                    'label' => __('Price Type'),
                    'required' => true,
                    'options' => $this->options->get('PriceType'),
                    // phpcs:ignore Generic.Files.LineLength.TooLong -- translatable phrase
                    'notice' => __('"Minimal price" is the lowest associated product price. "Sum of associated products prices" is the default quantity of each associated product multiplied with the price of the associated product and than summed together. If no default quantity is defined, it will output minimal price.'),
                ],
            ],
            'bundle' => [
                'bundle_associated_products_mode' => ['formElement' => 'select'] + [
                    'label' => __('How to add option products'),
                    'required' => true,
                    'options' => $this->options->get('AssociatedMode'),
                    // phpcs:ignore Generic.Files.LineLength.TooLong -- translatable phrase
                    'notice' => __('Bundle products are usually added as one item in the feed. Bundle sub-items could also be added.'),
                ],
                'bundle_combined_weight' => ['formElement' => 'select'] + [
                    'label' => __('Combined weight'),
                    'required' => true,
                    'options' => $this->options->get('Yesno'),
                    // phpcs:ignore Generic.Files.LineLength.TooLong -- translatable phrase
                    'notice' => __('Bundle items can be defined as Dynamic or Fixed weight. This feature overwrites the bundle defintition and goes for Dynamic weight by computing weight as sum of all sub-items.'),
                ],
            ],
            'shipping' => [
                'shipping_methods' => ['formElement' => 'multiselect'] + [
                    'label' => __('Methods'),
                    'required' => true,
                    'options' => $this->options->get('AvailableMethods'),
                    // phpcs:ignore Generic.Files.LineLength.TooLong -- translatable phrase
                    'notice' => __('Allowed shipping methods. Realtime carriers aren\'t allowed to avoid getting banned or to spam carriers\' servers. e.g. UPS, USPS, FedEx, DHL, Royal Mail. Please add/configure any realtime carriers in your Google Merchant account.'),
                ],
                'shipping_country' => ['formElement' => 'multiselect'] + [
                    'label' => __('Countries'),
                    'required' => true,
                    'options' => $this->options->get('Countryofmanufacture'),
                    // phpcs:ignore Generic.Files.LineLength.TooLong -- translatable phrase
                    'notice' => __('Shipping allowed countries. Select only a few countries the avoid a very long feed generation and to keep the feed size to a minimnum.'),
                ],
                'shipping_weight_column' => ['formElement' => 'select'] + [
                    'label' => __('Shipping Weight Column'),
                    'required' => false,
                    'options' => $this->options->get('Columns'),
                    // phpcs:ignore Generic.Files.LineLength.TooLong -- translatable phrase
                    'notice' => __('Set shipping weight column from which we calculate shipping costs. Columns must exist in Columns Map, save your config before looking for columns here.'),
                ],
                'shipping_only_minimum' => ['formElement' => 'select'] + [
                    'label' => __('Only Minimum Price'),
                    'required' => true,
                    'options' => $this->options->get('Yesno'),
                    // phpcs:ignore Generic.Files.LineLength.TooLong -- translatable phrase
                    'notice' => __('If there are more carriers/shipping methods than the shipping column will be filled with only the minimum price and the related carrier/shipping method.'),
                ],
                'shipping_only_free_shipping' => ['formElement' => 'select'] + [
                    'label' => __('Only Free Shipping'),
                    'required' => true,
                    'options' => $this->options->get('Yesno'),
                    'notice' => __('Add only free shipping when is available.'),
                ],
                'shipping_add_tax_to_price' => ['formElement' => 'select'] + [
                    'label' => __('Add Tax to Shipping Price'),
                    'required' => true,
                    'options' => $this->options->get('Yesno'),
                    'notice' => __('For US feeds column \'price\' should not include tax.'),
                ],
            ],
            'promotions' => [
                'promotions_enabled' => ['formElement' => 'select'] + [
                    'label' => __('Enable feed'),
                    'required' => true,
                    'options' => $this->options->get('Yesno'),
                    'notice' => __('Turns On/Off generation of google promotions feed.'),
                ],
            ],
        ];
        $sections['general']['file_feed'] = ['label' => __('File Name'), 'required' => true];
        $sections['general']['output_params_format'] = [
            'label' => __('Output Format'), 'formElement' => 'select', 'default' => 'delimited',
            'options' => [
                ['value' => 'delimited', 'label' => __('Delimited')],
                ['value' => 'xml', 'label' => __('XML')]
            ],
            'notice' => __('For XML output, use a .xml file name. XML output always uses UTF-8.')
        ];
        if ($model->getType() === 'generic') {
            foreach (['header' => __('XML Header'), 'item' => __('XML Item Element'),
                'footer' => __('XML Footer'), 'optional_columns' => __('XML Optional Columns'),
                'cdata_columns' => __('XML CDATA Columns')] as $key => $label) {
                $sections['general']['output_params_xml_' . $key] = [
                    'label' => $label,
                    'formElement' => in_array($key, ['header', 'footer'], true) ? 'textarea' : 'input',
                    'notice' => __('XML column lists are comma-separated names; * selects all columns.')
                ];
            }
        } else {
            $sections['general']['output_params_format']['notice'] =
                __('XML uses the destination preset automatically. Use a .xml file name.');
        }
        if ($model->getType() === 'openai_google_compatible') {
            $sections['general']['output_params_format']['options'] = [
                ['value' => 'delimited', 'label' => __('Delimited')]
            ];
            $sections['general']['output_params_format']['notice'] =
                __('This destination has no supported XML preset.');
        }
        $sections['general']['output_params_delimiter_other'] = ['label' => __('Other Delimiter')];
        if ($model->getType() === 'generic') {
            foreach (['enclose_cell' => __('Cell Enclosure'), 'enclose_escape' => __('Enclosure Escape'),
                'default_value' => __('Empty Cell Value')] as $key => $label) {
                $sections['general']['output_params_' . $key] = ['label' => $label];
            }
        }
        if ($model->getType() === 'google_shopping') {
            $sections['filters']['filters_skip_price_above'] = ['label' => __('Skip Products with Price above')];
            $sections['filters']['filters_skip_price_below'] = ['label' => __('Skip Products with Price below')];
        } else {
            unset($sections['promotions']);
        }
        if ($model->getType() === 'google_local_inventory') {
            unset(
                $sections['options'],
                $sections['shipping'],
                $sections['categories']['categories_locale'],
                $sections['categories']['categories_sort_mode'],
                $sections['configurable']['configurable_associated_products_link_add_unique'],
                $sections['configurable']['configurable_attribute_merge_value_separator'],
                $sections['grouped']['grouped_associated_products_link_add_unique'],
                $sections['bundle']['bundle_combined_weight']
            );
            $sections['categories']['categories_inventory_source_map'] = [
                'label' => __('Inventory Source to Google Store Code'), 'formElement' => 'textarea',
                // phpcs:ignore Generic.Files.LineLength.TooLong -- translatable phrase
                'notice' => __('Enter one mapping per line as source_code=store_code. Unmapped sources keep their Magento source code. Example: warehouse_indy=INDIANAPOLIS-01')
            ];
        }
        return $sections;
    }

    /**
     * Get the setup notice shown under the feed name for a feed type, or an empty string.
     *
     * @param string $type
     * @return string
     */
    public function setupNotice(string $type): string
    {
        $notes = [
            // phpcs:ignore Generic.Files.LineLength.TooLong -- translatable phrase
            'meta_catalog' => (string)__('Generate and review the TSV or XML file, then add its public URL as a scheduled data feed in Meta Commerce Manager. Schedule generation before Meta fetches it. Review condition, brand, GTIN/MPN, and variant mappings in Columns Map. Match feed IDs to your Meta Pixel or Conversions API content IDs. This template does not configure tracking or synchronize orders.'),
            // phpcs:ignore Generic.Files.LineLength.TooLong -- translatable phrase
            'microsoft_merchant_center' => (string)__('Generate and review the tab-delimited TXT or XML file. In Microsoft Merchant Center, create an online product feed and choose Automatically download file from URL. Use a public URL and schedule generation before the fetch. Match the store domain and target currency. Review identifiers, tax treatment, apparel fields, and shipping requirements for your target country; shipping is required for Austria and Germany. This template does not configure UET tracking or synchronize orders.'),
            // phpcs:ignore Generic.Files.LineLength.TooLong -- translatable phrase
            'tiktok_catalog' => (string)__('Generate and review the CSV or XML file, then add its public URL through Data Feed Schedule in TikTok Ads Manager > Assets > Catalog. Match the catalog currency and targeting location. Schedule generation before the fetch and keep sale prices current; TikTok does not use sale_price_effective_date to expire discounts. Match sku_id values to your TikTok Pixel content IDs. Review brand, identifiers, images, and variant mappings. This template does not configure tracking or synchronize TikTok Shop orders.'),
            // phpcs:ignore Generic.Files.LineLength.TooLong -- translatable phrase
            'openai_google_compatible' => (string)__('Confirm the Google-compatible profile with OpenAI before uploading. Register the merchant name and supported markets, map brand and real GTIN/MPN identifiers, and supply availability_date for preorders and backorders. Upload only products intended for discovery: search opt-out columns do not work in this profile. Generate full snapshots at least daily and use the agreed SFTP destination with a stable filename. Expiration and sale dates are metadata, not automatic removal or sale scheduling. This template does not enable checkout or account access.'),
            // phpcs:ignore Generic.Files.LineLength.TooLong -- translatable phrase
            'pinterest_catalog' => (string)__('Generate and review the TSV or XML file. In Pinterest Catalogs and product groups, add a data source with its public URL and choose the matching file format. Review country, language, currency, and claimed website before creating Pins. Schedule generation before the daily fetch; hosted URLs must use port 80 or 443. Use clear primary images at least 1000 by 1500 pixels and change image URLs when replacing images. Keep variant group IDs and tracking IDs consistent. This template does not configure the Pinterest tag or synchronize orders.'),
        ];
        return $notes[$type] ?? '';
    }
}
