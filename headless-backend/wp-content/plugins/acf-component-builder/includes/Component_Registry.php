<?php

declare(strict_types=1);

namespace ACB;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Holds all known components. Components come from two places:
 *
 *  1. Auto-discovery: every ACF Flexible Content field (and each of its
 *     layouts) is turned into a component definition automatically, so the
 *     plugin works the moment ACF fields exist — no manual setup required.
 *  2. Persisted overrides: any `acb_component` CPT entry can override or add
 *     to the auto-discovered set.
 *
 * Discovery is cached in a transient and rebuilt on the `acb_flush_cache` hook.
 *
 * @package ACFComponentBuilder
 */
final class Component_Registry
{
    private const CACHE_KEY = 'acb_discovered_components';

    /** @var array<string,Component> keyed by slug */
    private array $components = [];

    private bool $discovered = false;

    public function __construct(private ACF_Manager $acf)
    {
        add_action('acb_flush_cache', [$this, 'flush']);
    }

    public function flush(): void
    {
        delete_transient(self::CACHE_KEY);
        $this->components = [];
        $this->discovered = false;
    }

    /**
     * Build the component set from ACF field groups.
     */
    public function discover(): void
    {
        if ($this->discovered) {
            return;
        }
        $this->discovered = true;

        $cached = get_transient(self::CACHE_KEY);
        if (is_array($cached)) {
            foreach ($cached as $arr) {
                $c = Component::from_array($arr);
                $this->components[$c->slug] = $c;
            }
        } else {
            $this->build_from_acf();
            set_transient(
                self::CACHE_KEY,
                array_map(static fn(Component $c) => $c->to_array(), $this->components),
                HOUR_IN_SECONDS
            );
        }

        // Merge in persisted CPT components (these win).
        $this->merge_persisted();

        /**
         * Allow third parties to register / modify components.
         *
         * @param Component_Registry $registry
         */
        do_action('acb_register_component', $this);
    }

    private function build_from_acf(): void
    {
        foreach ($this->acf->get_field_groups() as $group) {
            $group_key = (string) ($group['key'] ?? '');
            $fields    = (array) ($group['fields'] ?? []);
            $this->scan_fields($fields, $group_key);
        }
    }

    /**
     * @param array<int,array<string,mixed>> $fields
     */
    private function scan_fields(array $fields, string $group_key): void
    {
        foreach ($fields as $field) {
            $type = (string) ($field['type'] ?? '');
            if ($type !== 'flexible_content') {
                continue;
            }

            $field_name = (string) ($field['name'] ?? '');
            $field_key  = (string) ($field['key'] ?? '');
            if ($field_name === '') {
                continue;
            }

            $layouts = (array) ($field['layouts'] ?? []);
            $layout_meta = [];
            foreach ($layouts as $layout) {
                $layout_meta[] = [
                    'name'       => (string) ($layout['name'] ?? ''),
                    'label'      => (string) ($layout['label'] ?? ''),
                    'key'        => (string) ($layout['key'] ?? ''),
                    'sub_fields' => Field_Resolver::normalise((array) ($layout['sub_fields'] ?? [])),
                ];
            }

            // Component for the whole flexible field.
            $slug = self::slugify($field_name);
            $this->components[$slug] = new Component(
                $slug,
                self::labelize($field_name),
                'flexible_content',
                $field_name,
                $field_key,
                $group_key,
                [],
                ['layouts' => $layout_meta]
            );

            // A component per individual layout too (addressable directly).
            foreach ($layout_meta as $lm) {
                if ($lm['name'] === '') {
                    continue;
                }
                $lslug = self::slugify($lm['name']);
                $this->components[$lslug] = new Component(
                    $lslug,
                    $lm['label'] !== '' ? $lm['label'] : self::labelize($lm['name']),
                    'layout',
                    $lm['name'],
                    $lm['key'],
                    $group_key,
                    $lm['sub_fields'],
                    ['parent_field' => $field_name]
                );
            }
        }
    }

    private function merge_persisted(): void
    {
        $posts = get_posts([
            'post_type'      => Post_Types::COMPONENT,
            'post_status'    => 'any',
            'numberposts'    => -1,
            'suppress_filters' => false,
        ]);
        foreach ($posts as $post) {
            $raw = get_post_meta($post->ID, '_acb_config', true);
            if (!is_string($raw) || $raw === '') {
                continue;
            }
            $data = json_decode($raw, true);
            if (!is_array($data)) {
                continue;
            }
            $data['name'] = $post->post_title ?: ($data['name'] ?? '');
            $c = Component::from_array($data);
            if ($c->slug !== '') {
                $this->components[$c->slug] = $c;
            }
        }
    }

    public function register(Component $component): void
    {
        $this->components[$component->slug] = $component;
    }

    public function get(string $slug): ?Component
    {
        $this->discover();
        return $this->components[$slug] ?? $this->components[self::slugify($slug)] ?? null;
    }

    /**
     * @return array<string,Component>
     */
    public function all(): array
    {
        $this->discover();
        return $this->components;
    }

    /**
     * @return array<string,Component> only flexible-content components
     */
    public function flexible_components(): array
    {
        return array_filter($this->all(), static fn(Component $c) => $c->is_flexible());
    }

    public static function slugify(string $name): string
    {
        $name = str_replace('_', '-', strtolower($name));
        return sanitize_title($name);
    }

    public static function labelize(string $name): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $name));
    }
}
