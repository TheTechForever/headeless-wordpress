<?php

declare(strict_types=1);

namespace ACB;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registers the internal, NON-public custom post types used to persist
 * component and template definitions.
 *
 * Storage decision (documented in /documentation/architecture.md):
 * - Components  -> CPT `acb_component`, config JSON stored in post meta `_acb_config`.
 * - Templates   -> CPT `acb_template`,  layout JSON stored in post meta `_acb_layout`.
 * CPTs give us the WordPress admin list tables, revisions, capabilities,
 * author/date columns and import/export for free, without inventing custom
 * database tables. They are private (not queryable on the front end).
 *
 * @package ACFComponentBuilder
 */
final class Post_Types
{
    public const COMPONENT = 'acb_component';
    public const TEMPLATE  = 'acb_template';
    public const BLOCK     = 'acb_block';

    public static function register(): void
    {
        $common = [
            'public'              => false,
            'publicly_queryable'  => false,
            'exclude_from_search' => true,
            'show_in_nav_menus'   => false,
            'show_in_rest'        => false,
            'hierarchical'        => false,
            'rewrite'             => false,
            'query_var'           => false,
            'can_export'          => true,
            'supports'            => ['title', 'author', 'revisions'],
            // We render our own admin UI, so keep these out of the default menu.
            'show_ui'             => false,
            'show_in_menu'        => false,
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
        ];

        register_post_type(self::COMPONENT, array_merge($common, [
            'label'  => __('ACB Components', 'acf-component-builder'),
            'labels' => ['name' => __('Components', 'acf-component-builder')],
        ]));

        register_post_type(self::TEMPLATE, array_merge($common, [
            'label'  => __('ACB Templates', 'acf-component-builder'),
            'labels' => ['name' => __('Templates', 'acf-component-builder')],
        ]));

        register_post_type(self::BLOCK, array_merge($common, [
            'label'  => __('ACB Blocks', 'acf-component-builder'),
            'labels' => ['name' => __('Blocks', 'acf-component-builder')],
        ]));
    }
}
