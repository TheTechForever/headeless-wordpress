<?php

declare(strict_types=1);

namespace ACB;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Detects ACF Pro and provides safe wrappers around ACF functions so the rest
 * of the plugin never fatals when ACF is missing.
 *
 * @package ACFComponentBuilder
 */
final class ACF_Manager
{
    /**
     * Is ACF (ideally Pro) available? Flexible Content, Repeater, Gallery and
     * Group are Pro-only, so we check for one of them as a Pro signal.
     */
    public function is_available(): bool
    {
        return function_exists('get_field')
            && function_exists('acf_add_local_field_group');
    }

    /**
     * Is ACF *Pro* specifically available (needed for flexible_content)?
     */
    public function is_pro(): bool
    {
        return $this->is_available() && class_exists('ACF') && function_exists('acf_get_field_type')
            && (bool) acf_get_field_type('flexible_content');
    }

    public function render_missing_notice(): void
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }
        echo '<div class="notice notice-error"><p><strong>' .
            esc_html__('ACF Component Builder', 'acf-component-builder') . '</strong> ' .
            esc_html__('requires Advanced Custom Fields PRO to be installed and active.', 'acf-component-builder') .
            '</p></div>';
    }

    /**
     * Safe get_field() wrapper.
     */
    public function get_field(string $selector, mixed $post_id = false): mixed
    {
        if (!function_exists('get_field')) {
            return null;
        }
        return \get_field($selector, $post_id);
    }

    /**
     * Return every registered ACF field group as arrays including their fields.
     *
     * @return array<int,array<string,mixed>>
     */
    public function get_field_groups(): array
    {
        if (!function_exists('acf_get_field_groups')) {
            return [];
        }
        $groups = acf_get_field_groups();
        $out    = [];
        foreach ($groups as $group) {
            $group['fields'] = function_exists('acf_get_fields')
                ? acf_get_fields($group)
                : [];
            $out[] = $group;
        }
        return $out;
    }
}
