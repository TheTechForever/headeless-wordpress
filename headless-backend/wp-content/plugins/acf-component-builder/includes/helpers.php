<?php

declare(strict_types=1);

/**
 * Public developer API for ACF Component Builder.
 *
 * @package ACFComponentBuilder
 */

if (!defined('ABSPATH')) {
    exit;
}

use ACB\Container;

if (!function_exists('acf_component')) {
    /**
     * Render a component by slug and ECHO it.
     *
     * @param string              $slug
     * @param array<string,mixed> $args
     */
    function acf_component(string $slug, array $args = []): void
    {
        echo ACB::render($slug, $args); // phpcs:ignore WordPress.Security.EscapeOutput -- Renderer escapes.
    }
}

if (!function_exists('acf_component_get')) {
    /**
     * Return a component's HTML as a string.
     *
     * @param array<string,mixed> $args
     */
    function acf_component_get(string $slug, array $args = []): string
    {
        return ACB::render($slug, $args);
    }
}

if (!function_exists('acf_component_flexible')) {
    /**
     * Render a whole ACF Flexible Content field and ECHO it.
     *
     * @param array<string,mixed> $args
     */
    function acf_component_flexible(string $field_name, array $args = []): void
    {
        echo ACB::render_flexible($field_name, $args); // phpcs:ignore WordPress.Security.EscapeOutput
    }
}

if (!function_exists('acf_component_layout')) {
    /**
     * Render a single flexible layout by its layout slug and ECHO it.
     *
     * @param array<string,mixed> $args
     */
    function acf_component_layout(string $layout_slug, array $args = []): void
    {
        echo ACB::render_layout($layout_slug, $args); // phpcs:ignore WordPress.Security.EscapeOutput
    }
}

/**
 * Static facade. All methods RETURN HTML so callers can echo or capture.
 *
 * Examples:
 *   echo ACB::render('hero');
 *   echo ACB::render_flexible('services_inner_detail_section');
 */
final class ACB
{
    public static function render(string $slug, array $args = []): string
    {
        $renderer = Container::get('renderer');
        if (!$renderer instanceof \ACB\Renderer) {
            return self::unavailable();
        }
        return $renderer->render($slug, $args);
    }

    public static function render_flexible(string $field_name, array $args = []): string
    {
        $renderer = Container::get('renderer');
        if (!$renderer instanceof \ACB\Renderer) {
            return self::unavailable();
        }
        return $renderer->render_flexible($field_name, $args);
    }

    public static function render_layout(string $layout_slug, array $args = []): string
    {
        $renderer = Container::get('renderer');
        if (!$renderer instanceof \ACB\Renderer) {
            return self::unavailable();
        }
        return $renderer->render_layout($layout_slug, $args);
    }

    /**
     * @return array<string,\ACB\Component>
     */
    public static function components(): array
    {
        $registry = Container::get('registry');
        return $registry instanceof \ACB\Component_Registry ? $registry->all() : [];
    }

    private static function unavailable(): string
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return "\n<!-- ACF Component Builder: not booted (is ACF Pro active?) -->\n";
        }
        return '';
    }
}
