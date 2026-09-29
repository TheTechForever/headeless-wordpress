<?php

declare(strict_types=1);

namespace ACB;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Inspects a component's ACF fields and produces a sensible default template
 * node-tree (the "smart template generation" feature). Heuristics:
 *
 *   - An image field + text/repeater content  => two-column row (image | content).
 *   - Layout name containing "right_image"     => content-left / image-right.
 *   - Content-only layouts                      => single centered column.
 *   - Repeater(title + wysiwyg)                 => looped heading + wysiwyg block.
 *
 * The output is a plain array (structured JSON) — never executable PHP.
 *
 * @package ACFComponentBuilder
 */
final class Template_Generator
{
    /**
     * @return array<string,mixed>
     */
    public static function generate(Component $component, ?string $layout_slug = null): array
    {
        $fields = $component->fields;

        // If this is a whole flexible field, generation is per-layout at render
        // time; return an empty shell.
        if ($component->is_flexible()) {
            return [
                'version'       => '1.0',
                'component'     => $component->slug,
                'layout_engine' => self::default_engine(),
                'settings'      => ['container' => 'container'],
                'children'      => [],
            ];
        }

        $image_field = self::first_of($fields, ['image']);
        $has_image   = $image_field !== null;
        $image_right = $layout_slug !== null && str_contains($layout_slug, 'right_image');
        $image_right = $image_right || ($layout_slug !== null && str_contains($layout_slug, 'right-image'));

        $content_children = self::build_content_children($fields);

        $engine   = self::default_engine();
        $settings = ['container' => 'container', 'class' => $component->slug . '-section'];

        if (!$has_image) {
            // Content-only: single column.
            return [
                'version'       => '1.0',
                'component'     => $component->slug,
                'layout_engine' => $engine,
                'settings'      => $settings,
                'children'      => [[
                    'type'     => 'row',
                    'class'    => $component->slug . '-row',
                    'children' => [[
                        'type'     => 'column',
                        'settings' => ['xs' => 12, 'md' => 10],
                        'class'    => $component->slug . '-content',
                        'children' => $content_children,
                    ]],
                ]],
            ];
        }

        // Two-column with image.
        $image_col = [
            'type'     => 'column',
            'settings' => ['xs' => 12, 'md' => 6],
            'class'    => $component->slug . '-image-col',
            'children' => [[
                'type'   => 'field',
                'field'  => $image_field['name'],
                'render' => 'image',
                'class'  => $component->slug . '-image',
            ]],
        ];
        $content_col = [
            'type'     => 'column',
            'settings' => ['xs' => 12, 'md' => 6],
            'class'    => $component->slug . '-content-col',
            'children' => $content_children,
        ];

        $cols = $image_right ? [$content_col, $image_col] : [$image_col, $content_col];

        return [
            'version'       => '1.0',
            'component'     => $component->slug,
            'layout_engine' => $engine,
            'settings'      => $settings,
            'children'      => [[
                'type'     => 'row',
                'settings' => ['align' => 'center'],
                'class'    => $component->slug . '-row',
                'children' => $cols,
            ]],
        ];
    }

    /**
     * Build the "content column" children: headings, repeaters, wysiwyg, links.
     *
     * @param array<int,array<string,mixed>> $fields
     * @return array<int,array<string,mixed>>
     */
    private static function build_content_children(array $fields): array
    {
        $children = [];
        foreach ($fields as $f) {
            $type = (string) ($f['type'] ?? '');
            $name = (string) ($f['name'] ?? '');
            if ($name === '') {
                continue;
            }
            switch ($type) {
                case 'image':
                    // handled separately as the image column
                    break;
                case 'text':
                    $is_title = str_contains($name, 'title');
                    $children[] = [
                        'type'   => 'field',
                        'field'  => $name,
                        'render' => $is_title ? 'heading' : 'text',
                        'tag'    => $is_title ? 'h2' : 'p',
                        'class'  => sanitize_html_class(str_replace('_', '-', $name)),
                    ];
                    break;
                case 'textarea':
                    $children[] = ['type' => 'field', 'field' => $name, 'render' => 'textarea', 'tag' => 'p'];
                    break;
                case 'wysiwyg':
                    $children[] = ['type' => 'field', 'field' => $name, 'render' => 'wysiwyg'];
                    break;
                case 'link':
                case 'url':
                    $children[] = ['type' => 'field', 'field' => $name, 'render' => 'link', 'class' => 'acb-btn'];
                    break;
                case 'repeater':
                    $children[] = self::build_repeater($f);
                    break;
                case 'group':
                    $children[] = [
                        'type'     => 'group',
                        'field'    => $name,
                        'children' => self::build_content_children((array) ($f['sub_fields'] ?? [])),
                    ];
                    break;
            }
        }
        return $children;
    }

    /**
     * @param array<string,mixed> $field
     * @return array<string,mixed>
     */
    private static function build_repeater(array $field): array
    {
        $name = (string) ($field['name'] ?? '');
        $sub  = (array) ($field['sub_fields'] ?? []);
        return [
            'type'     => 'repeater',
            'field'    => $name,
            'class'    => sanitize_html_class(str_replace('_', '-', $name)),
            'children' => self::build_content_children($sub),
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $fields
     * @param list<string>                   $types
     * @return array<string,mixed>|null
     */
    private static function first_of(array $fields, array $types): ?array
    {
        foreach ($fields as $f) {
            if (in_array((string) ($f['type'] ?? ''), $types, true)) {
                return $f;
            }
        }
        return null;
    }

    private static function default_engine(): string
    {
        $settings = (array) get_option('acb_settings', []);
        return (string) ($settings['default_engine'] ?? 'bootstrap');
    }
}
