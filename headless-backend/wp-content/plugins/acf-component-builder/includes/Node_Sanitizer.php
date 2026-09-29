<?php

declare(strict_types=1);

namespace ACB;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sanitises a template node tree coming from the visual builder BEFORE it is
 * persisted or rendered.
 *
 * This is a security boundary: the builder posts arbitrary JSON, and that JSON
 * later drives {@see Renderer}. We therefore whitelist every node type, every
 * settings key and every value shape here. Anything unrecognised is dropped —
 * never passed through. The tree is *data only*; it can never contain
 * executable PHP.
 *
 * @package ACFComponentBuilder
 */
final class Node_Sanitizer
{
    public const ENGINES     = ['bootstrap', 'flex', 'grid'];
    public const NODE_TYPES  = ['row', 'column', 'container', 'html', 'spacer', 'divider', 'field', 'repeater', 'group'];
    public const HTML_TAGS   = ['div', 'section', 'article', 'aside', 'header', 'footer', 'main', 'nav'];
    public const HEAD_TAGS   = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span'];
    public const RENDERERS   = ['text', 'heading', 'wysiwyg', 'image', 'link', 'gallery'];
    public const CONTAINERS  = ['', 'container', 'container-fluid'];
    public const JUSTIFY     = ['', 'start', 'center', 'end', 'between', 'around', 'evenly'];
    public const ALIGN       = ['', 'start', 'center', 'end', 'stretch', 'baseline'];
    public const DIRECTION   = ['row', 'row-reverse', 'column', 'column-reverse'];
    public const WRAP        = ['wrap', 'nowrap', 'wrap-reverse'];

    private const MAX_NODES = 500;
    private const MAX_DEPTH = 12;

    /** @var int Guards against pathological / hostile trees. */
    private static int $count = 0;

    /**
     * Sanitise a whole template document.
     *
     * @param array<string,mixed> $tree
     * @return array{layout_engine:string,settings:array<string,mixed>,children:array<int,array<string,mixed>>}
     */
    public static function tree(array $tree): array
    {
        self::$count = 0;

        $engine = (string) ($tree['layout_engine'] ?? 'bootstrap');
        if (!in_array($engine, self::ENGINES, true)) {
            $engine = 'bootstrap';
        }

        return [
            'layout_engine' => $engine,
            'settings'      => self::root_settings((array) ($tree['settings'] ?? [])),
            'children'      => self::children((array) ($tree['children'] ?? []), 1),
        ];
    }

    /**
     * Sanitise a single node subtree (used by the reusable-block library and
     * block insertion). Returns null if the root node is invalid.
     *
     * @param array<string,mixed> $node
     * @return array<string,mixed>|null
     */
    public static function subtree(array $node): ?array
    {
        self::$count = 0;
        return self::node($node, 1);
    }

    /**
     * @param array<string,mixed> $s
     * @return array<string,mixed>
     */
    private static function root_settings(array $s): array
    {
        $container = (string) ($s['container'] ?? '');
        if (!in_array($container, self::CONTAINERS, true)) {
            $container = '';
        }
        return [
            'class'     => self::css_classes((string) ($s['class'] ?? '')),
            'id'        => self::css_id((string) ($s['id'] ?? '')),
            'container' => $container,
        ];
    }

    /**
     * @param array<int,mixed> $nodes
     * @return array<int,array<string,mixed>>
     */
    private static function children(array $nodes, int $depth): array
    {
        if ($depth > self::MAX_DEPTH) {
            return [];
        }
        $out = [];
        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }
            if (self::$count >= self::MAX_NODES) {
                break;
            }
            $clean = self::node($node, $depth);
            if ($clean !== null) {
                $out[] = $clean;
            }
        }
        return $out;
    }

    /**
     * @param array<string,mixed> $node
     * @return array<string,mixed>|null
     */
    private static function node(array $node, int $depth): ?array
    {
        $type = (string) ($node['type'] ?? '');
        if (!in_array($type, self::NODE_TYPES, true)) {
            return null;
        }
        self::$count++;

        $base = [
            'type'  => $type,
            'class' => self::css_classes((string) ($node['class'] ?? '')),
            'id'    => self::css_id((string) ($node['id'] ?? '')),
        ];

        switch ($type) {
            case 'row':
            case 'column':
            case 'container':
                $base['settings'] = self::layout_settings($type, (array) ($node['settings'] ?? []));
                $base['children'] = self::children((array) ($node['children'] ?? []), $depth + 1);
                return $base;

            case 'html':
                $tag = (string) ($node['tag'] ?? 'div');
                $base['tag']      = in_array($tag, self::HTML_TAGS, true) ? $tag : 'div';
                $base['children'] = self::children((array) ($node['children'] ?? []), $depth + 1);
                return $base;

            case 'spacer':
                $base['height'] = self::clamp((int) ($node['height'] ?? 24), 0, 400);
                return $base;

            case 'divider':
                return $base;

            case 'field':
                $base['field']  = self::field_name((string) ($node['field'] ?? ''));
                $render         = (string) ($node['render'] ?? 'text');
                $base['render'] = in_array($render, self::RENDERERS, true) ? $render : 'text';
                if ($base['render'] === 'heading') {
                    $tag = (string) ($node['tag'] ?? 'h2');
                    $base['tag'] = in_array($tag, self::HEAD_TAGS, true) ? $tag : 'h2';
                }
                if (isset($node['link_text'])) {
                    $base['link_text'] = sanitize_text_field((string) $node['link_text']);
                }
                if (isset($node['size'])) {
                    $base['size'] = sanitize_key((string) $node['size']);
                }
                return $base;

            case 'repeater':
            case 'group':
                $base['field']    = self::field_name((string) ($node['field'] ?? ''));
                $base['children'] = self::children((array) ($node['children'] ?? []), $depth + 1);
                return $base;
        }

        return null;
    }

    /**
     * Whitelist layout settings per engine + node type.
     *
     * @param array<string,mixed> $s
     * @return array<string,mixed>
     */
    private static function layout_settings(string $type, array $s): array
    {
        $out = [];

        // Bootstrap column breakpoints.
        foreach (['xs', 'sm', 'md', 'lg', 'xl', 'xxl'] as $bp) {
            if (isset($s[$bp]) && $s[$bp] !== '') {
                $out[$bp] = self::clamp((int) $s[$bp], 1, 12);
            }
        }
        foreach (['offset_sm', 'offset_md', 'offset_lg', 'offset_xl'] as $off) {
            if (isset($s[$off]) && $s[$off] !== '') {
                $out[$off] = self::clamp((int) $s[$off], 0, 11);
            }
        }
        if (isset($s['order']) && $s['order'] !== '') {
            $out['order'] = self::clamp((int) $s['order'], 0, 12);
        }

        // Enumerated alignment.
        if (isset($s['justify']) && in_array((string) $s['justify'], self::JUSTIFY, true)) {
            $out['justify'] = (string) $s['justify'];
        }
        if (isset($s['align']) && in_array((string) $s['align'], self::ALIGN, true)) {
            $out['align'] = (string) $s['align'];
        }
        if (isset($s['align_self']) && in_array((string) $s['align_self'], self::ALIGN, true)) {
            $out['align_self'] = (string) $s['align_self'];
        }

        // Gutter (bootstrap 0-5).
        if (isset($s['gutter']) && $s['gutter'] !== '') {
            $out['gutter'] = self::clamp((int) $s['gutter'], 0, 5);
        }

        // Flex row.
        if (isset($s['direction']) && in_array((string) $s['direction'], self::DIRECTION, true)) {
            $out['direction'] = (string) $s['direction'];
        }
        if (isset($s['wrap']) && in_array((string) $s['wrap'], self::WRAP, true)) {
            $out['wrap'] = (string) $s['wrap'];
        }
        if (isset($s['gap']) && $s['gap'] !== '') {
            $out['gap'] = self::css_length((string) $s['gap']);
        }
        if (isset($s['column_gap']) && $s['column_gap'] !== '') {
            $out['column_gap'] = self::css_length((string) $s['column_gap']);
        }
        if (isset($s['row_gap']) && $s['row_gap'] !== '') {
            $out['row_gap'] = self::css_length((string) $s['row_gap']);
        }

        // Flex item.
        if (isset($s['grow']) && $s['grow'] !== '') {
            $out['grow'] = self::clamp((int) $s['grow'], 0, 12);
        }
        if (isset($s['basis']) && $s['basis'] !== '') {
            $out['basis'] = self::css_length((string) $s['basis']);
        }

        // Grid row column counts.
        foreach (['columns', 'tablet', 'mobile'] as $g) {
            if (isset($s[$g]) && $s[$g] !== '') {
                $out[$g] = self::clamp((int) $s[$g], 1, 12);
            }
        }

        // Container.
        if ($type === 'container' && isset($s['container'])) {
            $c = (string) $s['container'];
            $out['container'] = in_array($c, self::CONTAINERS, true) && $c !== '' ? $c : 'container';
        }

        return $out;
    }

    /* ------------------------------------------------------------------ */
    /* Scalar sanitisers                                                  */
    /* ------------------------------------------------------------------ */

    /** Sanitise a space-separated list of CSS class names. */
    public static function css_classes(string $value): string
    {
        $parts = preg_split('/\s+/', trim($value)) ?: [];
        $clean = [];
        foreach ($parts as $p) {
            $p = sanitize_html_class($p);
            if ($p !== '') {
                $clean[] = $p;
            }
        }
        return implode(' ', array_slice(array_unique($clean), 0, 30));
    }

    public static function css_id(string $value): string
    {
        return sanitize_html_class(trim($value));
    }

    /** ACF field/selector names: lowercase letters, numbers, underscores. */
    public static function field_name(string $value): string
    {
        $value = strtolower(trim($value));
        return (string) preg_replace('/[^a-z0-9_]/', '', $value);
    }

    /** Allow simple CSS lengths like "16px", "1rem", "50%", "auto". */
    public static function css_length(string $value): string
    {
        $value = trim($value);
        if ($value === 'auto') {
            return 'auto';
        }
        return preg_match('/^\d+(\.\d+)?(px|rem|em|%|vw|vh)?$/', $value) === 1 ? $value : '';
    }

    private static function clamp(int $n, int $min, int $max): int
    {
        return max($min, min($max, $n));
    }
}
