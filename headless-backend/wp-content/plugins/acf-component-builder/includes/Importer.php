<?php

declare(strict_types=1);

namespace ACB;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Imports and analyses ACF field-group JSON exports (the "SCF/ACF export"
 * format — a top-level array of field groups).
 *
 * Security: this class only ever treats input as data. It never evaluates
 * anything. All persistence goes through ACF's own import function.
 *
 * @package ACFComponentBuilder
 */
final class Importer
{
    /**
     * Decode + structurally validate JSON.
     *
     * @return array<int,array<string,mixed>>|\WP_Error
     */
    public static function parse(string $json)
    {
        $json = trim($json);
        if ($json === '') {
            return new \WP_Error('acb_empty', __('The file is empty.', 'acf-component-builder'));
        }
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return new \WP_Error('acb_invalid_json', __('The file is not valid JSON.', 'acf-component-builder'));
        }
        // ACF export is an array of groups; a single group is also acceptable.
        if (isset($data['key']) && isset($data['fields'])) {
            $data = [$data];
        }
        foreach ($data as $i => $group) {
            if (!is_array($group) || !isset($group['key'])) {
                return new \WP_Error(
                    'acb_invalid_structure',
                    sprintf(
                        /* translators: %d: item index */
                        __('Item %d is not a valid ACF field group (missing "key").', 'acf-component-builder'),
                        (int) $i
                    )
                );
            }
        }
        return $data;
    }

    /**
     * Produce a human-readable inventory: groups, flexible fields, layouts,
     * nested repeaters.
     *
     * @param array<int,array<string,mixed>> $data
     * @return array<int,array<string,mixed>>
     */
    public static function analyze(array $data): array
    {
        $report = [];
        foreach ($data as $group) {
            $flex = [];
            self::collect_flexible((array) ($group['fields'] ?? []), $flex);
            $report[] = [
                'key'      => (string) ($group['key'] ?? ''),
                'title'    => (string) ($group['title'] ?? ''),
                'existing' => self::group_exists((string) ($group['key'] ?? '')),
                'flexible' => $flex,
                'field_count' => count((array) ($group['fields'] ?? [])),
            ];
        }
        return $report;
    }

    /**
     * @param array<int,array<string,mixed>> $fields
     * @param array<int,array<string,mixed>> $out
     */
    private static function collect_flexible(array $fields, array &$out): void
    {
        foreach ($fields as $field) {
            if (($field['type'] ?? '') !== 'flexible_content') {
                continue;
            }
            $layouts = [];
            foreach ((array) ($field['layouts'] ?? []) as $layout) {
                $layouts[] = [
                    'name'      => (string) ($layout['name'] ?? ''),
                    'label'     => (string) ($layout['label'] ?? ''),
                    'slug'      => Component_Registry::slugify((string) ($layout['name'] ?? '')),
                    'has_repeater' => self::has_type((array) ($layout['sub_fields'] ?? []), 'repeater'),
                ];
            }
            $out[] = [
                'name'    => (string) ($field['name'] ?? ''),
                'label'   => (string) ($field['label'] ?? ''),
                'slug'    => Component_Registry::slugify((string) ($field['name'] ?? '')),
                'layouts' => $layouts,
            ];
        }
    }

    /**
     * @param array<int,array<string,mixed>> $fields
     */
    private static function has_type(array $fields, string $type): bool
    {
        foreach ($fields as $f) {
            if (($f['type'] ?? '') === $type) {
                return true;
            }
            if (in_array(($f['type'] ?? ''), ['repeater', 'group'], true)) {
                if (self::has_type((array) ($f['sub_fields'] ?? []), $type)) {
                    return true;
                }
            }
        }
        return false;
    }

    private static function group_exists(string $key): bool
    {
        return function_exists('acf_get_field_group') && (bool) acf_get_field_group($key);
    }

    /**
     * Import field groups into ACF.
     *
     * @param array<int,array<string,mixed>> $data
     * @param bool                           $overwrite  Overwrite existing groups with same key.
     * @return array{imported:int,skipped:int,errors:array<int,string>}
     */
    public static function import(array $data, bool $overwrite = false): array
    {
        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        if (!function_exists('acf_import_field_group')) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => [__('ACF import function unavailable.', 'acf-component-builder')]];
        }

        foreach ($data as $group) {
            $key = (string) ($group['key'] ?? '');
            if ($key === '') {
                $skipped++;
                continue;
            }
            $exists = self::group_exists($key);
            if ($exists && !$overwrite) {
                $skipped++;
                continue;
            }
            if ($exists && $overwrite) {
                $existing = acf_get_field_group($key);
                if ($existing && isset($existing['ID'])) {
                    $group['ID'] = $existing['ID'];
                }
            }
            // acf_import_field_group sanitises + persists as a real field group.
            $result = acf_import_field_group($group);
            if (is_array($result)) {
                $imported++;
            } else {
                $errors[] = sprintf(
                    /* translators: %s: group key */
                    __('Failed to import group %s.', 'acf-component-builder'),
                    $key
                );
            }
        }

        // Rebuild the component cache after importing.
        do_action('acb_flush_cache');

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }
}
