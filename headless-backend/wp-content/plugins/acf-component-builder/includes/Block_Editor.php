<?php

declare(strict_types=1);

namespace ACB;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registers the "ACF Component" Gutenberg block.
 *
 * This is the no-code way to place a component on ANY page or post: insert the
 * block, pick a component in the sidebar, and it renders through the same
 * {@see Renderer} the shortcode and PHP API use — using the current post's ACF
 * data. The block is dynamic (server-rendered), so edits to a component's
 * template are reflected everywhere the block is used without re-saving pages.
 *
 * @package ACFComponentBuilder
 */
final class Block_Editor
{
    public function __construct(
        private Component_Registry $registry,
        private Renderer $renderer
    ) {
    }

    public function init(): void
    {
        // Blocks must register on front end + REST (render) and in the editor.
        add_action('init', [$this, 'register'], 20);
    }

    public function register(): void
    {
        if (!function_exists('register_block_type')) {
            return; // Classic WordPress without blocks — shortcode/PHP still work.
        }

        $handle = 'acb-block';
        wp_register_script(
            $handle,
            ACB_PLUGIN_URL . 'assets/js/block.js',
            ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n'],
            ACB_VERSION,
            true
        );

        wp_localize_script($handle, 'ACB_BLOCK', [
            'components' => $this->component_choices(),
            'emptyText'  => __('Select a component in the block settings on the right.', 'acf-component-builder'),
            'noneText'   => __('No components found yet. Import your ACF JSON under “ACF Components”.', 'acf-component-builder'),
        ]);

        register_block_type('acb/component', [
            'api_version'     => 2,
            'title'           => __('ACF Component', 'acf-component-builder'),
            'description'     => __('Render an ACF Component Builder component on this page.', 'acf-component-builder'),
            'category'        => 'widgets',
            'icon'            => 'layout',
            'keywords'        => ['acf', 'component', 'acb', 'flexible'],
            'editor_script'   => $handle,
            'render_callback' => [$this, 'render_block'],
            'supports'        => ['html' => false, 'customClassName' => true, 'align' => ['wide', 'full']],
            'attributes'      => [
                'slug'      => ['type' => 'string', 'default' => ''],
                'className' => ['type' => 'string', 'default' => ''],
            ],
        ]);
    }

    /**
     * Server render. Falls back to a friendly placeholder in the editor when no
     * component is chosen, and to nothing on the front end.
     *
     * @param array<string,mixed> $attributes
     */
    public function render_block(array $attributes): string
    {
        $slug = isset($attributes['slug']) ? Component_Registry::slugify((string) $attributes['slug']) : '';
        if ($slug === '') {
            return $this->editor_placeholder(__('ACF Component — choose one in the sidebar.', 'acf-component-builder'));
        }

        $args = [];
        if (!empty($attributes['className'])) {
            $args['class'] = sanitize_text_field((string) $attributes['className']);
        }

        $component = $this->registry->get($slug);
        if ($component !== null && $component->is_flexible()) {
            $html = \ACB::render_flexible($component->field_name, $args);
        } else {
            $html = \ACB::render($slug, $args);
        }

        if (trim($html) === '' && $this->is_rest_render()) {
            return $this->editor_placeholder(
                __('Nothing to show yet — this component has no ACF data on the current post.', 'acf-component-builder')
            );
        }
        return $html;
    }

    /**
     * @return array<int,array{slug:string,label:string,type:string}>
     */
    private function component_choices(): array
    {
        $out = [];
        foreach ($this->registry->all() as $slug => $component) {
            $out[] = [
                'slug'  => (string) $slug,
                'label' => $component->name,
                'type'  => $component->source_type,
            ];
        }
        return $out;
    }

    private function editor_placeholder(string $text): string
    {
        if (!$this->is_rest_render()) {
            return '';
        }
        return '<div class="acb-block-placeholder" style="padding:24px;border:1px dashed #c3c4c7;border-radius:8px;'
            . 'text-align:center;color:#646970;background:#f6f7f7;">'
            . esc_html($text) . '</div>';
    }

    /** True when rendering inside the editor's block-renderer REST request. */
    private function is_rest_render(): bool
    {
        return defined('REST_REQUEST') && REST_REQUEST;
    }
}
