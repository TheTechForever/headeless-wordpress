<?php

declare(strict_types=1);

namespace ACB;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Understands ACF field structures recursively and resolves values in the
 * correct context (top-level vs. inside have_rows() loops).
 *
 * Value resolution rules:
 *  - Inside an active ACF row loop we must use get_sub_field().
 *  - At the top level we use get_field($selector, $post_id).
 * The Renderer tells us which context we are in.
 *
 * @package ACFComponentBuilder
 */
final class Field_Resolver
{
    /**
     * Reduce raw ACF field definitions to a lean, recursive structure the
     * renderer and template generator can reason about.
     *
     * @param array<int,array<string,mixed>> $fields
     * @return array<int,array<string,mixed>>
     */
    public static function normalise(array $fields): array
    {
        $out = [];
        foreach ($fields as $field) {
            $type = (string) ($field['type'] ?? '');
            $name = (string) ($field['name'] ?? '');

            // Skip presentational-only ACF fields.
            if (in_array($type, ['tab', 'message', 'accordion'], true)) {
                continue;
            }
            if ($name === '') {
                continue;
            }

            $entry = [
                'name'  => $name,
                'key'   => (string) ($field['key'] ?? ''),
                'label' => (string) ($field['label'] ?? ''),
                'type'  => $type,
            ];

            if (isset($field['return_format'])) {
                $entry['return_format'] = $field['return_format'];
            }

            if (in_array($type, ['repeater', 'group'], true)) {
                $entry['sub_fields'] = self::normalise((array) ($field['sub_fields'] ?? []));
            }

            if ($type === 'flexible_content') {
                $entry['layouts'] = [];
                foreach ((array) ($field['layouts'] ?? []) as $layout) {
                    $entry['layouts'][] = [
                        'name'       => (string) ($layout['name'] ?? ''),
                        'label'      => (string) ($layout['label'] ?? ''),
                        'sub_fields' => self::normalise((array) ($layout['sub_fields'] ?? [])),
                    ];
                }
            }

            $out[] = $entry;
        }
        return $out;
    }

    /**
     * Resolve a scalar/array value for a field in the current context.
     *
     * @param bool  $in_loop  True when inside a have_rows()/the_row() loop.
     * @param mixed $post_id  Post ID used only at top level.
     */
    public static function value(string $selector, bool $in_loop, mixed $post_id = false): mixed
    {
        if ($in_loop) {
            return function_exists('get_sub_field') ? get_sub_field($selector) : null;
        }
        return function_exists('get_field') ? get_field($selector, $post_id) : null;
    }

    /**
     * Find a normalised field definition by name within a list (non-recursive).
     *
     * @param array<int,array<string,mixed>> $fields
     * @return array<string,mixed>|null
     */
    public static function find(array $fields, string $name): ?array
    {
        foreach ($fields as $f) {
            if (($f['name'] ?? null) === $name) {
                return $f;
            }
        }
        return null;
    }
}
