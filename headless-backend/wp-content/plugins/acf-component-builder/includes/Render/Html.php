<?php

declare(strict_types=1);

namespace ACB\Render;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Small HTML helpers shared by renderers. Every method escapes.
 *
 * @package ACFComponentBuilder
 */
final class Html
{
    /**
     * Build a class attribute merging generated + custom classes safely.
     *
     * @param string|array<int,string> $classes
     */
    public static function classes(string|array $classes): string
    {
        if (is_string($classes)) {
            $classes = preg_split('/\s+/', trim($classes)) ?: [];
        }
        $clean = [];
        foreach ($classes as $c) {
            $c = trim((string) $c);
            if ($c === '') {
                continue;
            }
            $clean[] = sanitize_html_class($c);
        }
        $clean = array_values(array_unique(array_filter($clean)));
        return implode(' ', $clean);
    }

    /**
     * Build an attribute string from a node's common presentation keys.
     *
     * @param array<string,mixed> $node
     * @param string              $extra_classes Generated classes to merge in.
     */
    public static function attrs(array $node, string $extra_classes = ''): string
    {
        $out = '';

        $classes = trim($extra_classes . ' ' . (string) ($node['class'] ?? ''));
        $classes = self::classes($classes);
        if ($classes !== '') {
            $out .= ' class="' . esc_attr($classes) . '"';
        }

        if (!empty($node['id'])) {
            $out .= ' id="' . esc_attr(sanitize_html_class((string) $node['id'])) . '"';
        }

        if (!empty($node['style']) && is_string($node['style'])) {
            $out .= ' style="' . esc_attr($node['style']) . '"';
        }

        if (!empty($node['attrs']) && is_array($node['attrs'])) {
            foreach ($node['attrs'] as $k => $v) {
                $k = sanitize_key((string) $k);
                if ($k === '') {
                    continue;
                }
                // Only allow data-* / aria-* / role for safety.
                if (!preg_match('/^(data-|aria-)/', $k) && $k !== 'role') {
                    continue;
                }
                $out .= ' ' . $k . '="' . esc_attr((string) $v) . '"';
            }
        }

        return $out;
    }

    /**
     * Whitelist an HTML tag name, falling back to a default.
     *
     * @param list<string> $allowed
     */
    public static function tag(mixed $tag, string $default, array $allowed): string
    {
        $tag = strtolower((string) $tag);
        return in_array($tag, $allowed, true) ? $tag : $default;
    }
}
