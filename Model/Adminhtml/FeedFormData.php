<?php

declare(strict_types=1);

namespace MageOS\ShoppingFeed\Model\Adminhtml;

use Magento\Framework\Exception\LocalizedException;
use MageOS\ShoppingFeed\Model\Feed;
use MageOS\ShoppingFeed\Model\Feed\Upload;

/** Explicit boundary between the UI provider and the existing persistence contract. */
class FeedFormData
{
    public const PERSISTOR_KEY = 'mageos_shopping_feed_form';
    public const ROW_CONFIG = [
        'columns_product_columns', 'filters_map_replace_empty_columns', 'filters_find_and_replace',
        'filters_output_limit', 'filters_column_modifiers', 'filters_adwords_price_buckets',
        'configurable_map_inherit', 'grouped_map_inherit'
    ];
    private const SCHEDULE_FIELDS = ['id', 'start_at', 'batch_mode', 'batch_limit', 'delete'];
    private const UPLOAD_FIELDS = [
        'id', 'mode', 'host', 'port', 'username', 'password', 'path', 'gzip', 'host_key_fingerprint', 'delete'
    ];

    /**
     * Project a saved feed into the editor's form data, with credentials redacted.
     *
     * @param Feed $feed
     * @param array $configKeys
     * @return array
     * @throws LocalizedException
     */
    public function project(Feed $feed, array $configKeys): array
    {
        $data = [
            'id' => $feed->getId(), 'type' => $feed->getType(), 'name' => $feed->getName(),
            'store_id' => $feed->getStoreId(), 'use_microdata' => $feed->getUseMicrodata(), 'config' => []
        ];
        foreach ($configKeys as $key) {
            // Feed::getConfig($key) treats false and zero as missing. The editor must not.
            $data['config'][$key] = $feed->getConfig()->getData($key);
        }
        if (($data['config']['output_params_delimiter'] ?? '') === "\t") {
            $data['config']['output_params_delimiter'] = '\\t';
        }
        foreach (self::ROW_CONFIG as $key) {
            if (array_key_exists($key, $data['config'])) {
                $rows = $data['config'][$key];
                if ($rows === '' || $rows === null) {
                    $rows = [];
                }
                if (!is_array($rows) || array_filter($rows, static fn ($row): bool => !is_array($row))) {
                    throw new LocalizedException(__('Invalid stored configuration rows: %1.', $key));
                }
                $data['config'][$key] = array_values($rows);
                if (in_array($key, ['columns_product_columns', 'filters_map_replace_empty_columns'], true)) {
                    usort($data['config'][$key], static fn (array $a, array $b): int =>
                        ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
                }
                foreach ($data['config'][$key] as $index => &$row) {
                    $row['position'] = $index + 1;
                    if ($key === 'filters_map_replace_empty_columns' && !empty($row['static'])
                        && (empty($row['attribute']) || $row['attribute'] === 'directive_static_value')
                    ) {
                        // Older fallback rules stored their literal value separately from the directive parameter.
                        $row['attribute'] = 'directive_static_value';
                        $row['param'] = $row['static'];
                        unset($row['static']);
                    }
                }
                unset($row);
            }
        }
        $data['schedules'] = $this->children($feed->getSchedules(), self::SCHEDULE_FIELDS);
        $data['uploads'] = $this->children($feed->getUploads(), self::UPLOAD_FIELDS);
        return $this->redact($data);
    }

    /**
     * A JSON envelope keeps empty arrays, false, zero and nested parameter arrays intact.
     *
     * @param array $data
     * @return string
     * @throws \JsonException
     */
    public function encodeForProvider(array $data): string
    {
        // Magento's metadata sanitizer otherwise adds __disableTmpl keys inside literal parameter arrays.
        // Decode only after provider initialization, so saved text never becomes a UI template expression.
        return str_replace('$', '\\u0024', json_encode(
            $this->redact($data),
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
        ));
    }

    /**
     * A JSON envelope keeps empty arrays, false, zero and nested parameter arrays intact.
     *
     * @param mixed $json
     * @return array
     * @throws LocalizedException
     */
    public function decode($json): array
    {
        try {
            $data = is_string($json) ? json_decode($json, true, 64, JSON_THROW_ON_ERROR) : null;
        } catch (\JsonException $exception) {
            throw new LocalizedException(__('The feed form data is invalid. Reload the page and try again.'));
        }
        if (!is_array($data) || !is_array($data['config'] ?? null)) {
            throw new LocalizedException(__('The feed form data is invalid. Reload the page and try again.'));
        }
        if (($data['id'] ?? null) === '') {
            $data['id'] = null;
        }
        foreach (['schedules', 'uploads'] as $key) {
            if (array_key_exists($key, $data) && !is_array($data[$key])) {
                throw new LocalizedException(__('Invalid %1 rows.', $key));
            }
        }
        foreach (self::ROW_CONFIG as $key) {
            if (array_key_exists($key, $data['config'])) {
                if (!is_array($data['config'][$key])) {
                    throw new LocalizedException(__('Invalid configuration rows: %1.', $key));
                }
                foreach ($data['config'][$key] as &$row) {
                    if (!is_array($row)) {
                        throw new LocalizedException(__('Invalid configuration row: %1.', $key));
                    }
                }
                unset($row);
                // DynamicRows changes positions when dragging, not the backing array order.
                if (!in_array($key, ['columns_product_columns', 'filters_map_replace_empty_columns'], true)) {
                    usort($data['config'][$key], static fn (array $a, array $b): int =>
                        (float)($a['position'] ?? 0) <=> (float)($b['position'] ?? 0));
                }
                foreach ($data['config'][$key] as &$row) {
                    unset($row['record_id'], $row['position'], $row['initialize']);
                }
                unset($row);
            }
        }
        foreach (['schedules' => self::SCHEDULE_FIELDS, 'uploads' => self::UPLOAD_FIELDS] as $key => $fields) {
            if (array_key_exists($key, $data)) {
                $data[$key] = $this->children($data[$key], $fields);
                // Unsaved rows deleted in the UI must never create child records.
                $data[$key] = array_values(array_filter($data[$key], static fn (array $row): bool =>
                    !empty($row['id']) || empty($row['delete'])));
            }
        }
        return array_intersect_key($data, array_flip([
            'id', 'type', 'name', 'store_id', 'use_microdata', 'config', 'schedules', 'uploads'
        ]));
    }

    /**
     * Never put decrypted or newly entered credentials in provider JSON or recovery sessions.
     *
     * @param array $data
     * @return array
     */
    public function redact(array $data): array
    {
        foreach ($data['uploads'] ?? [] as $index => $upload) {
            $data['uploads'][$index]['password'] = !empty($upload['id']) ? Upload::OBSCURED_VALUE : '';
        }
        return $data;
    }

    /**
     * Keep only the allowed fields of each schedule or upload row.
     *
     * @param mixed $rows
     * @param array $fields
     * @return array
     * @throws LocalizedException
     */
    private function children($rows, array $fields): array
    {
        if (!is_array($rows)) {
            return [];
        }
        $result = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                throw new LocalizedException(__('Invalid feed child row.'));
            }
            $result[] = array_intersect_key($row, array_flip($fields)) + ['id' => null];
        }
        return $result;
    }
}
