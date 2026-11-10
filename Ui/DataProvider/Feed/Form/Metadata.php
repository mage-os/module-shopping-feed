<?php

declare(strict_types=1);

namespace MageOS\ShoppingFeed\Ui\DataProvider\Feed\Form;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Phrase;
use Magento\Store\Model\System\Store;
use MageOS\ShoppingFeed\Model\Feed;
use MageOS\ShoppingFeed\Model\Adminhtml\FeedFormData;
use MageOS\ShoppingFeed\Model\Product\Category\CollectionProvider;
use MageOS\ShoppingFeed\Model\Taxonomy\ProviderFactory;

/** Native UI component metadata. Plugins/modifiers can extend this without PHP form renderers. */
class Metadata
{
    /**
     * Constructor
     *
     * @param Fields $fields
     * @param Options $options
     * @param Parameters $parameters
     * @param Store $stores
     * @param AuthorizationInterface $authorization
     * @param CollectionProvider $categories
     * @param ProviderFactory $taxonomy
     * @param Promotions $promotions
     */
    public function __construct(
        private Fields $fields,
        private Options $options,
        private Parameters $parameters,
        private Store $stores,
        private AuthorizationInterface $authorization,
        private CollectionProvider $categories,
        private ProviderFactory $taxonomy,
        private Promotions $promotions
    ) {
    }

    /**
     * Get the config keys the editor form reads and writes for the feed.
     *
     * @param Feed $feed
     * @return array
     */
    public function configKeys(Feed $feed): array
    {
        $sectionKeys = [];
        foreach ($this->fields->get($feed) as $fields) {
            $sectionKeys[] = array_keys($fields);
        }
        $keys = array_merge([], ...$sectionKeys);
        $keys = array_unique(
            array_merge($keys, FeedFormData::ROW_CONFIG, [
                'categories_provider_taxonomy_by_category', 'filters_product_conditions'
            ])
        );
        if ($feed->getType() === 'google_shopping') {
            $keys[] = 'promotions_provider_widget';
        } else {
            $keys = array_diff($keys, ['filters_adwords_price_buckets']);
        }
        return array_values(array_diff($keys, ['name']));
    }

    /**
     * Get the native UI component form metadata for the feed.
     *
     * @param Feed $feed
     * @return array
     */
    public function get(Feed $feed): array
    {
        $meta = [];
        $labels = [
            'general' => __('General'), 'columns' => __('Columns Map'), 'categories' => __('Categories Map'),
            'filters' => __('Product Filters'), 'options' => __('Product Options'),
            'configurable' => __('Configurable Products'),
            'grouped' => __('Grouped Products'), 'bundle' => __('Bundle Products'), 'shipping' => __('Shipping'),
            'schedule' => __('Schedule'), 'uploads' => __('Uploads'), 'promotions' => __('Google Promotions')
        ];
        foreach ($labels as $section => $label) {
            $meta[$section] = $this->node([
                'componentType' => 'fieldset', 'label' => $label, 'collapsible' => true,
                'opened' => $section === 'general', 'dataScope' => '', 'sortOrder' => count($meta) * 10
            ]);
        }
        $standard = $this->fields->get($feed);
        foreach ($standard as $section => $fields) {
            foreach ($fields as $key => $config) {
                if (in_array(
                    $key,
                    ['filters_attribute_sets', 'filters_skip_column_empty', 'shipping_methods', 'shipping_country'],
                    true
                )) {
                    $config['required'] = false;
                }
                $meta[$section]['children'][$key] = $this->field(
                    $key === 'name' ? $key : 'config.' . $key,
                    $config + ['sortOrder' => count($meta[$section]['children'] ?? []) * 10]
                );
            }
        }
        if ($feed->getType() === 'google_local_inventory') {
            unset($meta['options'], $meta['shipping']);
        }
        foreach (['id', 'type'] as $key) {
            $meta['general']['children'][$key] = $this->field($key, ['visible' => false]);
        }
        $meta['general']['children']['store_id'] = $this->field('store_id', [
            'label' => __('Store View'), 'formElement' => 'select', 'sortOrder' => 5,
            'options' => $this->stores->getStoreValuesForForm(false, false), 'required' => true,
            'notice' => __(
                'Save and reload after changing the store view to refresh its category and attribute options.'
            )
        ]);
        $meta['general']['children']['name']['arguments']['data']['config']['notice'] =
            $this->fields->setupNotice((string)$feed->getType());
        if ($feed->getType() === 'google_shopping') {
            $meta['general']['children']['use_microdata'] = $this->field('use_microdata', [
                'label' => __('Use for microdata'), 'formElement' => 'select',
                'options' => $this->options->get('Yesno'),
                'notice' => __('Only one feed per store should supply microdata.')
            ]);
        }
        $this->dependency(
            $meta['general']['children']['general_stock_attribute_code'],
            'general.general_use_default_stock',
            '0'
        );
        $this->dependency(
            $meta['general']['children']['output_params_delimiter_other'],
            'general.output_params_delimiter',
            'other'
        );

        $columns = $this->select(__('Column'), 'Columns');
        $parameter = [
            'label' => __('Parameters'), 'component' => 'MageOS_ShoppingFeed/js/form/parameter',
            'elementTmpl' => 'MageOS_ShoppingFeed/form/parameter', 'definitions' => $this->parameters->get($feed),
            'imports' => ['attribute' => '${ $.parentName }.attribute:value', '__disableTmpl' => ['attribute' => false]]
        ];
        $meta['filters']['children']['filters_product_conditions'] = $this->field(
            'config.filters_product_conditions',
            [
                'label' => __('Product selection conditions'),
                'component' => 'MageOS_ShoppingFeed/js/form/product-selection',
                'elementTmpl' => 'MageOS_ShoppingFeed/form/product-selection',
                'definition' => ($parameter['definitions']['directive_conditional_value'] ?? [])
                    + ['selection' => true],
                'sortOrder' => 35,
                'notice' => __('Only matching output products are included. Leave conditions empty to disable.')
            ]
        );
        $mapping = ['order' => ['label' => __('Order')],
            'column' => ['label' => __('Column'), 'required' => true],
            'attribute' => $this->select(__('Attribute or Directive'), 'DirectivesAndAttributes')
                + ['required' => true],
            'param' => $parameter];
        $meta['columns']['children']['columns_product_columns'] = $this->rows(
            __('Columns'),
            'config',
            $mapping,
            true
        );
        $mapping['column'] = $columns;
        $meta['filters']['children']['filters_map_replace_empty_columns'] = $this->rows(
            __('Replace empty values'),
            'config',
            $mapping,
            true
        );
        $meta['filters']['children']['filters_find_and_replace'] = $this->rows(__('Find and Replace'), 'config', [
            'find' => ['label' => __('Find'), 'required' => true], 'replace' => ['label' => __('Replace')],
            'column' => $columns
        ]);
        $meta['filters']['children']['filters_output_limit'] = $this->rows(__('Limit column output'), 'config', [
            'column' => $this->select(__('Column'), 'LimitColumns'),
            'limit' => ['label' => __('Max characters'), 'required' => true,
                'validation' => ['validate-digits' => true, 'validate-greater-than-zero' => true]]
        ]);
        if ($feed->getType() === 'google_shopping') {
            $meta['filters']['children']['filters_adwords_price_buckets'] = $this->rows(
                __('Adwords Price Buckets'),
                'config',
                [
                    'pricefrom' => [
                        'label' => __('Price from'), 'required' => true,
                        'validation' => ['validate-zero-or-greater' => true]
                    ],
                    'priceto' => [
                        'label' => __('Price to'), 'required' => true,
                        'validation' => ['validate-greater-than-zero' => true]
                    ],
                    'label' => ['label' => __('Bucket label')]
                ]
            );
        }
        foreach (['configurable', 'grouped'] as $section) {
            $meta[$section]['children'][$section . '_map_inherit'] = $this->rows(
                __('Value inheritance by column'),
                'config',
                [
                    'column' => $columns, 'from' => $this->select(__('Map value from'), 'Inheritance'),
                    'extra' => [
                        'label' => __('Only for attributes'),
                        'notice' => __('Comma-separated attribute codes')
                    ]
                ]
            );
        }
        $categoryRows = $this->categories->getCategories($feed);
        $provider = $feed->isTaxonomyAutocompleteEnabled() ? $this->taxonomy->create($feed) : null;
        $meta['categories']['children']['categories_provider_taxonomy_by_category'] = $this->field(
            'config.categories_provider_taxonomy_by_category',
            [
                'label' => __('Taxonomy by Magento Category'), 'component' => 'MageOS_ShoppingFeed/js/form/categories',
                'elementTmpl' => 'MageOS_ShoppingFeed/form/categories', 'categories' => $categoryRows,
                'additionalClasses' => ['admin__field-wide' => true],
                'taxonomyOptions' => $provider ? $provider->getTaxonomyList() : [],
                'localInventory' => $feed->getType() === 'google_local_inventory'
            ]
        );
        $meta['schedule']['children']['schedules'] = $this->rows(__('Schedules'), '', [
            'id' => ['visible' => false], 'start_at' => $this->select(__('Start At'), 'StartAt') + ['default' => 1],
            'batch_mode' => $this->select(__('Batch Mode'), 'Yesno') + ['default' => 0],
            'batch_limit' => ['label' => __('Batch Limit'), 'validation' => ['validate-digits' => true]],
        ]);
        $meta['uploads']['children']['uploads'] = $this->rows(__('Upload Destinations'), '', [
            'id' => ['visible' => false], 'mode' => $this->select(__('Mode'), 'TransferModes') + ['default' => 'sftp'],
            'host' => ['label' => __('Host'), 'required' => true],
            'host_key_fingerprint' => [
                'label' => __('SFTP Host Key Fingerprint'),
                'notice' => __('Required for SFTP. Enter the SHA256 fingerprint confirmed with your provider.')
            ],
            'port' => [
                'label' => __('Port'), 'default' => 22, 'required' => true,
                'validation' => ['validate-digits' => true]
            ],
            'username' => ['label' => __('Username'), 'required' => true],
            'password' => [
                'label' => __('Password'), 'elementTmpl' => 'MageOS_ShoppingFeed/form/password', 'required' => true
            ],
            'path' => ['label' => __('Path')], 'gzip' => $this->select(__('Gzip'), 'Yesno') + ['default' => 0]
        ]);
        if ($feed->getType() === 'google_shopping') {
            $meta['promotions']['children']['counter'] = $this->field('config.promotions_provider_widget.counter', [
                'label' => __('Submit as new promotion'),
                'component' => 'MageOS_ShoppingFeed/js/form/promotion-counter',
                'elementTmpl' => 'MageOS_ShoppingFeed/form/promotion-counter'
            ]);
            foreach ($this->promotions->rules($feed) as $rule) {
                $id = (string)$rule->getId();
                $scope = 'config.promotions_provider_widget.promotion.' . $id . '.';
                $couponNotice = $rule->getCode() ? __('Coupon Code: %1', $rule->getCode()) : __('No coupon code');
                if ($rule->getApplyToShipping() && (int)$rule->getCouponType() === 1) {
                    $couponNotice = __('Shipping promotions require a coupon code.');
                }
                $children = [
                    'include' => $this->select(__('Include'), 'Yesno') + ['notice' => $couponNotice],
                    'title' => [
                        'label' => __('Promotion Title'), 'validation' => ['max_text_length' => 60],
                        'notice' => __(
                            'Use a clear title that follows Google Merchant Center promotion editorial requirements.'
                        )
                    ]
                ];
                $dateLabels = [
                    'date' => ['from' => __('Effective from'), 'to' => __('Effective to')],
                    'display' => ['from' => __('Display from'), 'to' => __('Display to')],
                ];
                foreach ($dateLabels as $key => $boundaries) {
                    foreach ($boundaries as $boundary => $label) {
                        $children[$key . '.' . $boundary] = ['label' => $label,
                            'component' => 'MageOS_ShoppingFeed/js/form/promotion-date',
                            'formElement' => 'date', 'dataType' => 'date', 'options' => ['dateFormat' => 'yyyy/MM/dd'],
                            'inputDateFormat' => 'yyyy/MM/dd', 'outputDateFormat' => 'yyyy/MM/dd'];
                    }
                }
                $meta['promotions']['children']['rule_' . $id] = $this->node([
                    'componentType' => 'fieldset', 'label' => $rule->getName(), 'collapsible' => true,
                    'opened' => false, 'dataScope' => ''
                ]);
                foreach ($children as $key => $config) {
                    $meta['promotions']['children']['rule_' . $id]['children'][str_replace('.', '_', $key)] =
                        $this->field($scope . $key, $config);
                }
            }
        } else {
            unset($meta['promotions']);
        }
        return $meta;
    }

    /**
     * Build a select field config from a named option source.
     *
     * @param Phrase $label
     * @param string $source
     * @return array
     */
    private function select(Phrase $label, string $source): array
    {
        return ['label' => $label, 'formElement' => 'select', 'options' => $this->options->get($source)];
    }

    /**
     * Build a field node, applying required validation, ACL read-only state and the select components.
     *
     * @param string $scope
     * @param array $config
     * @return array
     */
    private function field(string $scope, array $config): array
    {
        $config += ['componentType' => 'field', 'formElement' => 'input', 'dataType' => 'text', 'dataScope' => $scope];
        if (!empty($config['required'])) {
            $config['validation']['required-entry'] = true;
        }
        if (!$this->authorization->isAllowed('MageOS_ShoppingFeed::save')) {
            $config['disabled'] = true;
        }
        // Native selects otherwise replace an unavailable stored option with their first option.
        if (in_array($config['formElement'], ['select', 'multiselect'], true)) {
            $config['component'] = 'MageOS_ShoppingFeed/js/form/' . $config['formElement'];
        }
        return $this->node($config);
    }

    /**
     * Build a dynamic rows node with one record template made of the given fields.
     *
     * @param Phrase $label
     * @param string $scope
     * @param array $fields
     * @param bool $ordered
     * @return array
     */
    private function rows(Phrase $label, string $scope, array $fields, bool $ordered = false): array
    {
        $canSave = $this->authorization->isAllowed('MageOS_ShoppingFeed::save');
        $result = $this->node([
            'componentType' => 'dynamicRows', 'label' => $label, 'dataScope' => $scope,
            'addButtonLabel' => __('Add'), 'addButton' => $canSave, 'deleteProperty' => 'delete', 'deleteValue' => true,
            'renderDefaultRecord' => false, 'dndConfig' => ['enabled' => $canSave && !$ordered], 'sortOrder' => 500,
            'additionalClasses' => ['admin__field-wide' => true, 'shopping-feed-mapping' => $ordered]
        ]);
        if ($ordered) {
            // Keep existing duplicate order values intact; the explicit Order field controls mapping priority.
            $result['arguments']['data']['config']['component'] = 'MageOS_ShoppingFeed/js/form/ordered-rows';
        }
        $result['children']['record'] = $this->node([
            'componentType' => 'container', 'component' => 'Magento_Ui/js/dynamic-rows/record',
            'isTemplate' => true, 'is_collection' => true, 'positionProvider' => 'position'
        ]);
        $fields += ['position' => ['visible' => false]];
        foreach ($fields as $key => $config) {
            $result['children']['record']['children'][$key] = $this->field($key, $config + ['sortOrder' =>
                count($result['children']['record']['children'] ?? []) * 10]);
        }
        if ($canSave) {
            $result['children']['record']['children']['action_delete'] = $this->node([
                'componentType' => 'actionDelete', 'dataType' => 'text', 'label' => __('Action'), 'sortOrder' => 999
            ]);
        }
        return $result;
    }

    /**
     * Show a field only while another field has the given value.
     *
     * @param array $field
     * @param string $source
     * @param string $value
     * @return void
     */
    private function dependency(array &$field, string $source, string $value): void
    {
        $config = &$field['arguments']['data']['config'];
        $config['component'] = 'MageOS_ShoppingFeed/js/form/dependent-' . ($config['formElement'] ?? 'input');
        $config['imports']['dependency'] = '${ $.ns }.${ $.ns }.' . $source . ':value';
        $config['imports']['__disableTmpl']['dependency'] = false;
        $config['showWhen'] = $value;
    }

    /**
     * Wrap a component config in the UI component metadata node structure.
     *
     * @param array $config
     * @return array
     */
    private function node(array $config): array
    {
        return ['arguments' => ['data' => ['config' => $config]]];
    }
}
