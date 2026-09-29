<?php

declare(strict_types=1);

namespace ACB;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX endpoints backing the visual template builder & live preview.
 *
 * Every handler:
 *   - verifies the `acb_builder` nonce (check_ajax_referer), and
 *   - checks the `manage_options` capability,
 * then reads request data defensively. Node trees are always run through
 * {@see Node_Sanitizer} before persistence or rendering.
 *
 * @package ACFComponentBuilder
 */
final class Ajax
{
    private const CAP   = 'manage_options';
    private const NONCE = 'acb_builder';

    public function __construct(
        private Component_Registry $registry,
        private Renderer $renderer,
        private Template_Store $store,
        private Block_Store $blocks
    ) {
    }

    public function init(): void
    {
        add_action('wp_ajax_acb_component_fields', [$this, 'component_fields']);
        add_action('wp_ajax_acb_preview_posts', [$this, 'preview_posts']);
        add_action('wp_ajax_acb_load_template', [$this, 'load_template']);
        add_action('wp_ajax_acb_save_template', [$this, 'save_template']);
        add_action('wp_ajax_acb_delete_template', [$this, 'delete_template']);
        add_action('wp_ajax_acb_duplicate_template', [$this, 'duplicate_template']);
        add_action('wp_ajax_acb_preview', [$this, 'preview']);

        // Phase 3: block library.
        add_action('wp_ajax_acb_list_blocks', [$this, 'list_blocks']);
        add_action('wp_ajax_acb_save_block', [$this, 'save_block']);
        add_action('wp_ajax_acb_get_block', [$this, 'get_block']);
        add_action('wp_ajax_acb_delete_block', [$this, 'delete_block']);

        // Phase 3: versioning.
        add_action('wp_ajax_acb_list_versions', [$this, 'list_versions']);
        add_action('wp_ajax_acb_restore_version', [$this, 'restore_version']);

        // Phase 3: import / export.
        add_action('wp_ajax_acb_export_template', [$this, 'export_template']);
        add_action('wp_ajax_acb_import_template', [$this, 'import_template']);
    }

    /* ------------------------------------------------------------------ */
    /* Handlers                                                           */
    /* ------------------------------------------------------------------ */

    /** Return the selectable fields for a component (for the field picker). */
    public function component_fields(): void
    {
        $this->guard();
        $slug      = $this->req_slug();
        $component = $this->registry->get($slug);
        if ($component === null) {
            wp_send_json_error(['message' => __('Component not found.', 'acf-component-builder')], 404);
        }
        wp_send_json_success([
            'slug'   => $component->slug,
            'name'   => $component->name,
            'fields' => $this->flatten_fields($component->fields),
        ]);
    }

    /** Candidate posts to preview against (those whose type could hold the data). */
    public function preview_posts(): void
    {
        $this->guard();

        $types = get_post_types(['public' => true], 'names');
        unset($types['attachment']);

        $posts = get_posts([
            'post_type'        => array_values($types),
            'post_status'      => 'publish',
            'numberposts'      => 50,
            'orderby'          => 'modified',
            'order'            => 'DESC',
            'suppress_filters' => false,
        ]);

        $out = [];
        foreach ($posts as $p) {
            $out[] = [
                'id'    => (int) $p->ID,
                'label' => sprintf('%s — %s', get_post_type($p), get_the_title($p) ?: '#' . $p->ID),
            ];
        }
        wp_send_json_success(['posts' => $out]);
    }

    /** Load a saved template (or an auto-generated seed for a component). */
    public function load_template(): void
    {
        $this->guard();

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id > 0) {
            $record = $this->store->get($id);
            if ($record === null) {
                wp_send_json_error(['message' => __('Template not found.', 'acf-component-builder')], 404);
            }
            wp_send_json_success($record);
        }

        // Seed from the component's auto-generated tree.
        $slug      = $this->req_slug();
        $component = $this->registry->get($slug);
        if ($component === null) {
            wp_send_json_error(['message' => __('Component not found.', 'acf-component-builder')], 404);
        }
        $seed = Template_Generator::generate($component, $component->source_type === 'layout' ? $slug : null);
        wp_send_json_success([
            'id'    => 0,
            'title' => $component->name,
            'slug'  => $slug,
            'tree'  => Node_Sanitizer::tree($seed),
        ]);
    }

    public function save_template(): void
    {
        $this->guard();

        $id    = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
        $slug  = $this->req_slug();
        $tree  = $this->req_tree();

        $post_id = $this->store->save($id, $title, $slug, $tree);
        if ($post_id === 0) {
            wp_send_json_error(['message' => __('Could not save the template.', 'acf-component-builder')], 500);
        }
        wp_send_json_success([
            'id'      => $post_id,
            'message' => __('Template saved.', 'acf-component-builder'),
        ]);
    }

    public function delete_template(): void
    {
        $this->guard();
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0 || !$this->store->delete($id)) {
            wp_send_json_error(['message' => __('Could not delete the template.', 'acf-component-builder')], 400);
        }
        wp_send_json_success(['message' => __('Template deleted.', 'acf-component-builder')]);
    }

    public function duplicate_template(): void
    {
        $this->guard();
        $id  = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $new = $id > 0 ? $this->store->duplicate($id) : null;
        if ($new === null) {
            wp_send_json_error(['message' => __('Could not duplicate the template.', 'acf-component-builder')], 400);
        }
        wp_send_json_success(['id' => $new]);
    }

    /** Render a live preview of an unsaved node tree. */
    public function preview(): void
    {
        $this->guard();

        $slug      = $this->req_slug();
        $component = $this->registry->get($slug);
        if ($component === null) {
            wp_send_json_error(['message' => __('Component not found.', 'acf-component-builder')], 404);
        }

        $tree    = Node_Sanitizer::tree($this->req_tree());
        $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;

        $html = $this->renderer->preview($component, $tree, ['post_id' => $post_id ?: false]);

        wp_send_json_success([
            'html'   => $html,
            'engine' => (string) ($tree['layout_engine'] ?? 'bootstrap'),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Phase 3: block library                                             */
    /* ------------------------------------------------------------------ */

    public function list_blocks(): void
    {
        $this->guard();
        wp_send_json_success(['blocks' => $this->blocks->all()]);
    }

    public function save_block(): void
    {
        $this->guard();
        $id    = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
        $cat   = isset($_POST['category']) ? sanitize_text_field(wp_unslash($_POST['category'])) : '';
        $desc  = isset($_POST['description']) ? sanitize_textarea_field(wp_unslash($_POST['description'])) : '';
        $node  = $this->req_node();

        if ($node === null) {
            wp_send_json_error(['message' => __('Nothing to save as a block.', 'acf-component-builder')], 400);
        }
        $post_id = $this->blocks->save($id, $title, $cat, $desc, $node);
        if ($post_id === 0) {
            wp_send_json_error(['message' => __('Could not save the block.', 'acf-component-builder')], 500);
        }
        wp_send_json_success(['id' => $post_id, 'message' => __('Block saved to library.', 'acf-component-builder')]);
    }

    public function get_block(): void
    {
        $this->guard();
        $id   = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $tree = $id > 0 ? $this->blocks->tree($id) : null;
        if ($tree === null) {
            wp_send_json_error(['message' => __('Block not found.', 'acf-component-builder')], 404);
        }
        wp_send_json_success(['node' => $tree]);
    }

    public function delete_block(): void
    {
        $this->guard();
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0 || !$this->blocks->delete($id)) {
            wp_send_json_error(['message' => __('Could not delete the block.', 'acf-component-builder')], 400);
        }
        wp_send_json_success(['message' => __('Block deleted.', 'acf-component-builder')]);
    }

    /* ------------------------------------------------------------------ */
    /* Phase 3: versioning                                                */
    /* ------------------------------------------------------------------ */

    public function list_versions(): void
    {
        $this->guard();
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        wp_send_json_success(['versions' => $id > 0 ? $this->store->versions($id) : []]);
    }

    public function restore_version(): void
    {
        $this->guard();
        $id    = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $index = isset($_POST['index']) ? (int) $_POST['index'] : -1;
        $tree  = $id > 0 && $index >= 0 ? $this->store->restore($id, $index) : null;
        if ($tree === null) {
            wp_send_json_error(['message' => __('Could not restore that version.', 'acf-component-builder')], 400);
        }
        wp_send_json_success([
            'tree'    => $tree,
            'message' => __('Version restored.', 'acf-component-builder'),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Phase 3: import / export                                           */
    /* ------------------------------------------------------------------ */

    public function export_template(): void
    {
        $this->guard();
        $id      = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $payload = $id > 0 ? $this->store->export($id) : null;
        if ($payload === null) {
            wp_send_json_error(['message' => __('Nothing to export yet — save the template first.', 'acf-component-builder')], 400);
        }
        wp_send_json_success(['payload' => $payload]);
    }

    public function import_template(): void
    {
        $this->guard();
        if (!isset($_POST['payload'])) {
            wp_send_json_error(['message' => __('No import data received.', 'acf-component-builder')], 400);
        }
        $raw     = wp_unslash($_POST['payload']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
        $payload = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($payload)) {
            wp_send_json_error(['message' => __('Import data is not valid JSON.', 'acf-component-builder')], 400);
        }
        $new = $this->store->import($payload);
        if ($new === null) {
            wp_send_json_error(['message' => __('That does not look like an ACB template export.', 'acf-component-builder')], 400);
        }
        wp_send_json_success(['id' => $new, 'message' => __('Template imported.', 'acf-component-builder')]);
    }

    /* ------------------------------------------------------------------ */
    /* Helpers                                                            */
    /* ------------------------------------------------------------------ */

    private function guard(): void
    {
        check_ajax_referer(self::NONCE, 'nonce');
        if (!current_user_can(self::CAP)) {
            wp_send_json_error(['message' => __('Permission denied.', 'acf-component-builder')], 403);
        }
    }

    private function req_slug(): string
    {
        $slug = isset($_POST['slug']) ? sanitize_text_field(wp_unslash($_POST['slug'])) : '';
        return Component_Registry::slugify($slug);
    }

    /**
     * Decode the posted node tree. The builder posts JSON as a string to keep
     * nested structure intact through PHP's form handling.
     *
     * @return array<string,mixed>
     */
    private function req_tree(): array
    {
        if (!isset($_POST['tree'])) {
            return [];
        }
        $raw  = wp_unslash($_POST['tree']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
        $data = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($data) ? $data : [];
    }

    /**
     * Decode a single posted node subtree (for block saves).
     *
     * @return array<string,mixed>|null
     */
    private function req_node(): ?array
    {
        if (!isset($_POST['node'])) {
            return null;
        }
        $raw  = wp_unslash($_POST['node']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
        $data = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($data) ? $data : null;
    }

    /**
     * Flatten a normalised field tree into a pickable list, keeping the path
     * for repeater/group sub-fields and a suggested renderer per field.
     *
     * @param array<int,array<string,mixed>> $fields
     * @param string                         $prefix
     * @return array<int,array<string,mixed>>
     */
    private function flatten_fields(array $fields, string $prefix = ''): array
    {
        $out = [];
        foreach ($fields as $f) {
            $name = (string) ($f['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $type  = (string) ($f['type'] ?? 'text');
            $label = (string) ($f['label'] ?? $name);

            $entry = [
                'name'    => $name,
                'label'   => $prefix !== '' ? $prefix . ' › ' . $label : $label,
                'type'    => $type,
                'render'  => $this->suggest_render($type),
                'is_loop' => in_array($type, ['repeater', 'group'], true),
            ];
            if (!empty($f['sub_fields']) && is_array($f['sub_fields'])) {
                $entry['sub_fields'] = $this->flatten_fields($f['sub_fields'], $label);
            }
            $out[] = $entry;
        }
        return $out;
    }

    private function suggest_render(string $acf_type): string
    {
        return match ($acf_type) {
            'image'                       => 'image',
            'gallery'                     => 'gallery',
            'wysiwyg', 'textarea'         => 'wysiwyg',
            'link', 'page_link', 'url'    => 'link',
            default                       => 'text',
        };
    }
}
