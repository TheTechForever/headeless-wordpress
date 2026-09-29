<?php

declare(strict_types=1);

namespace ACB;

use ACB\Layout\Layout_Factory;
use ACB\Render\Html;
use ACB\Render\Renderer_Registry;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Turns a component + resolved template into safe frontend HTML.
 *
 * Context handling is central: ACF resolves sub-fields with get_sub_field()
 * only while inside a have_rows()/the_row() loop. The renderer therefore
 * threads an $in_loop flag through the whole node tree so every field is read
 * from the correct scope — top-level fields via get_field(), looped fields via
 * get_sub_field().
 *
 * @package ACFComponentBuilder
 */
final class Renderer
{
    private Renderer_Registry $fields;

    public function __construct(
        private Component_Registry $registry,
        private Template_Resolver $templates,
        private ACF_Manager $acf
    ) {
        $this->fields = new Renderer_Registry();
    }

    /**
     * Render a component by slug. Returns HTML (does not echo).
     *
     * @param array<string,mixed> $args  Supports: post_id, template, class.
     */
    public function render(string $slug, array $args = []): string
    {
        $component = $this->registry->get($slug);
        if ($component === null) {
            return $this->debug_comment("component not found: {$slug}");
        }

        if ($component->is_flexible()) {
            return $this->render_flexible($component->field_name, $args);
        }

        $post_id  = $args['post_id'] ?? false;
        $resolved = $this->templates->resolve($component);

        Asset_Manager::mark_used($component->slug);

        /**
         * Filter/replace the resolved template before rendering.
         *
         * @param array     $resolved
         * @param Component $component
         */
        $resolved = apply_filters('acb_component_template', $resolved, $component);

        do_action('acb_before_render_component', $component, $args);

        $html = $this->render_resolved($resolved, $component, $post_id, false, $args);

        /**
         * Filter the final HTML for a component.
         *
         * @param string    $html
         * @param Component $component
         */
        $html = (string) apply_filters('acb_after_render_component', $html, $component, $args);

        do_action('acb_after_render_component', $component, $args);

        return $html;
    }

    /**
     * Render a whole ACF Flexible Content field: iterate rows, resolve the
     * component for each layout, render each layout's template.
     *
     * @param array<string,mixed> $args
     */
    public function render_flexible(string $field_name, array $args = []): string
    {
        $post_id = $args['post_id'] ?? false;

        if (!function_exists('have_rows')) {
            return $this->debug_comment('ACF not available for flexible content');
        }
        if (!have_rows($field_name, $post_id)) {
            return $this->debug_comment("no rows for flexible field: {$field_name}");
        }

        $out = '';
        while (have_rows($field_name, $post_id)) {
            the_row();
            $layout = (string) get_row_layout();
            if ($layout === '') {
                continue;
            }
            $slug      = Component_Registry::slugify($layout);
            $component = $this->registry->get($slug);
            if ($component === null) {
                $out .= $this->debug_comment("no component for layout: {$layout}");
                continue;
            }

            Asset_Manager::mark_used($component->slug);

            $resolved = $this->templates->resolve($component, $slug);
            $resolved = apply_filters('acb_component_template', $resolved, $component);

            do_action('acb_before_render_component', $component, $args);

            // We ARE inside a the_row() context => in_loop = true.
            $out .= $this->render_resolved($resolved, $component, $post_id, true, $args);

            do_action('acb_after_render_component', $component, $args);
        }

        return $out;
    }

    /**
     * Render one specific flexible layout by its layout slug (thin wrapper).
     *
     * @param array<string,mixed> $args
     */
    public function render_layout(string $layout_slug, array $args = []): string
    {
        $component = $this->registry->get($layout_slug);
        if ($component === null) {
            return $this->debug_comment("no component for layout: {$layout_slug}");
        }
        $parent = (string) ($component->meta['parent_field'] ?? '');
        if ($parent === '') {
            return $this->render($layout_slug, $args);
        }
        // Render only matching rows of the parent flexible field.
        $post_id = $args['post_id'] ?? false;
        if (!function_exists('have_rows') || !have_rows($parent, $post_id)) {
            return $this->debug_comment("no rows for: {$parent}");
        }
        $out = '';
        while (have_rows($parent, $post_id)) {
            the_row();
            if (Component_Registry::slugify((string) get_row_layout()) !== $component->slug) {
                continue;
            }
            $resolved = $this->templates->resolve($component, $component->slug);
            $out .= $this->render_resolved($resolved, $component, $post_id, true, $args);
        }
        return $out;
    }

    /**
     * Render an arbitrary (unsaved) JSON node tree for a component — used by
     * the visual builder's live preview. Sets up the correct ACF loop context
     * so sub-fields resolve exactly as they will once the template is saved.
     *
     * @param array<string,mixed> $tree
     * @param array<string,mixed> $args Supports: post_id, class.
     */
    public function preview(Component $component, array $tree, array $args = []): string
    {
        $post_id = $args['post_id'] ?? false;

        // Non-flexible: render at top level.
        if (!$component->is_flexible() && $component->source_type !== 'layout') {
            return $this->render_json_template($tree, $post_id, false, $args);
        }

        // Flexible field as a whole: render the first matching row per layout.
        $parent = (string) ($component->meta['parent_field'] ?? $component->field_name);
        if (!function_exists('have_rows') || $parent === '' || !have_rows($parent, $post_id)) {
            // No data to loop; render top-level so structure is still visible.
            return $this->render_json_template($tree, $post_id, false, $args);
        }

        $target = $component->source_type === 'layout' ? $component->slug : null;
        $out    = '';
        while (have_rows($parent, $post_id)) {
            the_row();
            $layout_slug = Component_Registry::slugify((string) get_row_layout());
            if ($target !== null && $layout_slug !== $target) {
                continue;
            }
            $out .= $this->render_json_template($tree, $post_id, true, $args);
            if ($target !== null) {
                break; // Preview a single representative row for a specific layout.
            }
        }
        return $out !== '' ? $out : $this->render_json_template($tree, $post_id, false, $args);
    }

    /**
     * @param array{type:string,path?:string,template?:array<string,mixed>} $resolved
     * @param array<string,mixed> $args
     */
    private function render_resolved(array $resolved, Component $component, mixed $post_id, bool $in_loop, array $args): string
    {
        if (($resolved['type'] ?? '') === 'php' && !empty($resolved['path'])) {
            return $this->render_php_template((string) $resolved['path'], $component, $post_id, $in_loop, $args);
        }
        $template = (array) ($resolved['template'] ?? []);
        return $this->render_json_template($template, $post_id, $in_loop, $args);
    }

    /**
     * Include a PHP template with helpful vars in scope. Themes/plugins own
     * these files, so PHP execution here is trusted (never from user input).
     *
     * @param array<string,mixed> $args
     */
    private function render_php_template(string $path, Component $component, mixed $post_id, bool $in_loop, array $args): string
    {
        ob_start();
        // Vars exposed to the template.
        $acb = [
            'component' => $component,
            'post_id'   => $post_id,
            'in_loop'   => $in_loop,
            'args'      => $args,
            // Helper to fetch a field respecting loop context.
            'field'     => static fn(string $name) => Field_Resolver::value($name, $in_loop, $post_id),
        ];
        include $path;
        return (string) ob_get_clean();
    }

    /**
     * Render a structured JSON node tree.
     *
     * @param array<string,mixed> $template
     * @param array<string,mixed> $args
     */
    private function render_json_template(array $template, mixed $post_id, bool $in_loop, array $args): string
    {
        $engine   = Layout_Factory::make((string) ($template['layout_engine'] ?? 'bootstrap'));
        $settings = (array) ($template['settings'] ?? []);
        $children = (array) ($template['children'] ?? []);

        $inner = '';
        foreach ($children as $child) {
            $inner .= $this->render_node((array) $child, $engine, $post_id, $in_loop, $args);
        }

        // Optional wrapping <section> + container.
        $section_class = trim((string) ($settings['class'] ?? '') . ' ' . (string) ($args['class'] ?? ''));
        $section_attr  = Html::attrs(['class' => $section_class, 'id' => $settings['id'] ?? '']);
        $container     = (string) ($settings['container'] ?? '');

        $html = '<section' . $section_attr . '>';
        if ($container !== '') {
            $cclass = $engine->container_classes(['container' => $container]);
            $html  .= '<div class="' . esc_attr($cclass) . '">' . $inner . '</div>';
        } else {
            $html .= $inner;
        }
        $html .= '</section>';
        return $html;
    }

    /**
     * Recursively render a single node.
     *
     * @param array<string,mixed> $node
     * @param array<string,mixed> $args
     */
    private function render_node(array $node, Layout\Layout_Engine_Interface $engine, mixed $post_id, bool $in_loop, array $args): string
    {
        $type = (string) ($node['type'] ?? '');

        switch ($type) {
            case 'row':
                $cls   = $engine->row_classes((array) ($node['settings'] ?? []));
                $style = $engine->inline_style((array) ($node['settings'] ?? []));
                $inner = $this->render_children($node, $engine, $post_id, $in_loop, $args);
                return $this->wrap('div', $node, $cls, $style, $inner);

            case 'column':
                $cls   = $engine->column_classes((array) ($node['settings'] ?? []));
                $style = $engine->inline_style((array) ($node['settings'] ?? []));
                $inner = $this->render_children($node, $engine, $post_id, $in_loop, $args);
                return $this->wrap('div', $node, $cls, $style, $inner);

            case 'container':
                $cls   = $engine->container_classes((array) ($node['settings'] ?? []));
                $inner = $this->render_children($node, $engine, $post_id, $in_loop, $args);
                return $this->wrap('div', $node, $cls, '', $inner);

            case 'html':
                $inner = $this->render_children($node, $engine, $post_id, $in_loop, $args);
                $tag   = Html::tag($node['tag'] ?? 'div', 'div', ['div', 'section', 'article', 'aside', 'header', 'footer']);
                return $this->wrap($tag, $node, '', '', $inner);

            case 'spacer':
                $h = (int) ($node['height'] ?? 24);
                return '<div class="acb-spacer" style="height:' . $h . 'px" aria-hidden="true"></div>';

            case 'divider':
                return '<hr' . Html::attrs($node, 'acb-divider') . ' />';

            case 'field':
                return $this->render_field_node($node, $post_id, $in_loop);

            case 'repeater':
                return $this->render_repeater_node($node, $engine, $post_id, $in_loop, $args);

            case 'group':
                return $this->render_group_node($node, $engine, $post_id, $in_loop, $args);

            default:
                return '';
        }
    }

    /**
     * @param array<string,mixed> $node
     * @param array<string,mixed> $args
     */
    private function render_children(array $node, Layout\Layout_Engine_Interface $engine, mixed $post_id, bool $in_loop, array $args): string
    {
        $out = '';
        foreach ((array) ($node['children'] ?? []) as $child) {
            $out .= $this->render_node((array) $child, $engine, $post_id, $in_loop, $args);
        }
        return $out;
    }

    /**
     * @param array<string,mixed> $node
     */
    private function render_field_node(array $node, mixed $post_id, bool $in_loop): string
    {
        $name = (string) ($node['field'] ?? '');
        if ($name === '') {
            return '';
        }
        $value = Field_Resolver::value($name, $in_loop, $post_id);

        /**
         * Filter a resolved field value before rendering.
         *
         * @param mixed  $value
         * @param string $name
         */
        $value = apply_filters('acb_field_value', $value, $name, $node);

        $render_type = (string) ($node['render'] ?? 'text');
        return $this->fields->for($render_type)->render($value, $node);
    }

    /**
     * @param array<string,mixed> $node
     * @param array<string,mixed> $args
     */
    private function render_repeater_node(array $node, Layout\Layout_Engine_Interface $engine, mixed $post_id, bool $in_loop, array $args): string
    {
        $name = (string) ($node['field'] ?? '');
        if ($name === '' || !function_exists('have_rows')) {
            return '';
        }

        // have_rows() is context-aware: inside a row it loops the sub-repeater;
        // at top level it loops the top-level repeater on $post_id.
        $has = $in_loop ? have_rows($name) : have_rows($name, $post_id);
        if (!$has) {
            return '';
        }

        $wrap_class = Html::classes((string) ($node['class'] ?? 'acb-repeater'));
        $out        = '<div class="' . esc_attr($wrap_class) . '">';

        // Iterate. have_rows() is context-aware (sub-repeater vs top-level).
        if ($in_loop) {
            while (have_rows($name)) {
                the_row();
                $out .= '<div class="acb-repeater__item">';
                foreach ((array) ($node['children'] ?? []) as $child) {
                    // Children are now sub-fields => in_loop = true.
                    $out .= $this->render_node((array) $child, $engine, $post_id, true, $args);
                }
                $out .= '</div>';
            }
        } else {
            while (have_rows($name, $post_id)) {
                the_row();
                $out .= '<div class="acb-repeater__item">';
                foreach ((array) ($node['children'] ?? []) as $child) {
                    $out .= $this->render_node((array) $child, $engine, $post_id, true, $args);
                }
                $out .= '</div>';
            }
        }

        $out .= '</div>';
        return $out;
    }

    /**
     * @param array<string,mixed> $node
     * @param array<string,mixed> $args
     */
    private function render_group_node(array $node, Layout\Layout_Engine_Interface $engine, mixed $post_id, bool $in_loop, array $args): string
    {
        $name = (string) ($node['field'] ?? '');
        if ($name === '' || !function_exists('have_rows')) {
            return '';
        }
        $has = $in_loop ? have_rows($name) : have_rows($name, $post_id);
        if (!$has) {
            return '';
        }
        $out = '<div' . Html::attrs($node, 'acb-group') . '>';
        // A group behaves like a single-iteration loop.
        if ($in_loop) {
            if (have_rows($name)) {
                the_row();
                foreach ((array) ($node['children'] ?? []) as $child) {
                    $out .= $this->render_node((array) $child, $engine, $post_id, true, $args);
                }
            }
        } else {
            if (have_rows($name, $post_id)) {
                the_row();
                foreach ((array) ($node['children'] ?? []) as $child) {
                    $out .= $this->render_node((array) $child, $engine, $post_id, true, $args);
                }
            }
        }
        $out .= '</div>';
        return $out;
    }

    /**
     * @param array<string,mixed> $node
     */
    private function wrap(string $tag, array $node, string $generated_class, string $style, string $inner): string
    {
        $merged = $node;
        if ($style !== '') {
            $merged['style'] = trim(($node['style'] ?? '') . ';' . $style, ';');
        }
        return '<' . $tag . Html::attrs($merged, $generated_class) . '>' . $inner . '</' . $tag . '>';
    }

    private function debug_comment(string $msg): string
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return "\n<!-- ACF Component Builder: " . esc_html($msg) . " -->\n";
        }
        return "\n<!-- ACF Component Builder -->\n";
    }
}
