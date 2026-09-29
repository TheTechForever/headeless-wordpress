<?php

declare(strict_types=1);

namespace ACB;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Resolves the template used to render a component / layout.
 *
 * Resolution order (highest priority first):
 *   1. Saved `acb_template` CPT bound to the component slug.
 *   2. Child theme PHP override:  {stylesheet}/acb-components/{slug}.php
 *   3. Parent theme PHP override:  {template}/acb-components/{slug}.php
 *   4. Plugin PHP template:        templates/{parent}/{slug}.php  or templates/{slug}.php
 *   5. Auto-generated JSON template (introspects the component's fields).
 *
 * A resolved template is either:
 *   ['type' => 'php',  'path' => '/abs/file.php']
 *   ['type' => 'json', 'template' => [ ...node tree... ]]
 *
 * @package ACFComponentBuilder
 */
final class Template_Resolver
{
    private const THEME_DIR = 'acb-components';

    /**
     * @return array{type:string,path?:string,template?:array<string,mixed>}
     */
    public function resolve(Component $component, ?string $layout_slug = null): array
    {
        $slug = $layout_slug ?? $component->slug;

        // 1. Persisted CPT template.
        $json = $this->find_cpt_template($slug);
        if ($json !== null) {
            return ['type' => 'json', 'template' => $json];
        }

        // 2/3. Theme overrides.
        $php = $this->locate_theme_template($slug);
        if ($php !== null) {
            return ['type' => 'php', 'path' => $php];
        }

        // 4. Plugin PHP template.
        $php = $this->locate_plugin_template($component, $slug);
        if ($php !== null) {
            return ['type' => 'php', 'path' => $php];
        }

        // 5. Auto-generate.
        return [
            'type'     => 'json',
            'template' => Template_Generator::generate($component, $layout_slug),
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    private function find_cpt_template(string $slug): ?array
    {
        $posts = get_posts([
            'post_type'   => Post_Types::TEMPLATE,
            'post_status' => 'publish',
            'numberposts' => 1,
            'meta_key'    => '_acb_component_slug',
            'meta_value'  => $slug,
            'suppress_filters' => false,
        ]);
        if (!$posts) {
            return null;
        }
        $raw = get_post_meta($posts[0]->ID, '_acb_layout', true);
        if (!is_string($raw) || $raw === '') {
            return null;
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    private function locate_theme_template(string $slug): ?string
    {
        $file  = self::THEME_DIR . '/' . sanitize_file_name($slug) . '.php';
        $found = locate_template([$file], false); // checks child then parent theme.
        return $found !== '' ? $found : null;
    }

    private function locate_plugin_template(Component $component, string $slug): ?string
    {
        $candidates = [];
        $parent = (string) ($component->meta['parent_field'] ?? '');
        if ($parent !== '') {
            $candidates[] = ACB_PLUGIN_DIR . 'templates/' . Component_Registry::slugify($parent) . '/' . $slug . '.php';
        }
        $candidates[] = ACB_PLUGIN_DIR . 'templates/' . $component->slug . '/' . $slug . '.php';
        $candidates[] = ACB_PLUGIN_DIR . 'templates/' . $slug . '.php';

        foreach ($candidates as $c) {
            if (is_readable($c)) {
                return $c;
            }
        }
        return null;
    }
}
