<?php

declare(strict_types=1);

namespace ACB;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Read/write layer for saved templates (CPT {@see Post_Types::TEMPLATE}).
 *
 * A template row stores:
 *   - post_title                : human label
 *   - meta _acb_component_slug   : the component/layout slug it renders
 *   - meta _acb_layout           : JSON node tree (sanitised)
 *   - meta _acb_engine           : convenience copy of the layout engine
 *
 * The node tree is always passed through {@see Node_Sanitizer} before it is
 * written, so nothing unsafe can ever reach the renderer.
 *
 * @package ACFComponentBuilder
 */
final class Template_Store
{
    /**
     * List all saved templates as lightweight rows for the manager table.
     *
     * @return array<int,array{id:int,title:string,slug:string,engine:string,modified:string}>
     */
    public function all(): array
    {
        $posts = get_posts([
            'post_type'        => Post_Types::TEMPLATE,
            'post_status'      => ['publish', 'draft'],
            'numberposts'      => 200,
            'orderby'          => 'modified',
            'order'            => 'DESC',
            'suppress_filters' => false,
        ]);

        $rows = [];
        foreach ($posts as $p) {
            $rows[] = [
                'id'       => (int) $p->ID,
                'title'    => $p->post_title !== '' ? $p->post_title : __('(untitled)', 'acf-component-builder'),
                'slug'     => (string) get_post_meta($p->ID, '_acb_component_slug', true),
                'engine'   => (string) (get_post_meta($p->ID, '_acb_engine', true) ?: 'bootstrap'),
                'modified' => (string) get_post_modified_time('Y-m-d H:i', false, $p),
            ];
        }
        return $rows;
    }

    /**
     * Full record for the builder.
     *
     * @return array{id:int,title:string,slug:string,tree:array<string,mixed>}|null
     */
    public function get(int $id): ?array
    {
        $post = get_post($id);
        if (!$post || $post->post_type !== Post_Types::TEMPLATE) {
            return null;
        }
        $raw  = get_post_meta($id, '_acb_layout', true);
        $tree = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];
        if (!is_array($tree)) {
            $tree = [];
        }
        return [
            'id'    => (int) $id,
            'title' => (string) $post->post_title,
            'slug'  => (string) get_post_meta($id, '_acb_component_slug', true),
            'tree'  => Node_Sanitizer::tree($tree),
        ];
    }

    public function find_id_by_slug(string $slug): ?int
    {
        $posts = get_posts([
            'post_type'        => Post_Types::TEMPLATE,
            'post_status'      => ['publish', 'draft'],
            'numberposts'      => 1,
            'meta_key'         => '_acb_component_slug',
            'meta_value'       => $slug,
            'fields'           => 'ids',
            'suppress_filters' => false,
        ]);
        return $posts ? (int) $posts[0] : null;
    }

    /**
     * Create or update a template. Returns the post ID.
     *
     * A snapshot of the *previous* saved tree is pushed onto the version stack
     * before overwriting, so every save is recoverable (see {@see versions()}
     * and {@see restore()}).
     *
     * @param array<string,mixed> $tree Raw (unsanitised) node tree from the builder.
     */
    public function save(int $id, string $title, string $slug, array $tree): int
    {
        $tree  = Node_Sanitizer::tree($tree);
        $title = sanitize_text_field($title);
        $slug  = Component_Registry::slugify($slug);

        $postarr = [
            'post_type'   => Post_Types::TEMPLATE,
            'post_status' => 'publish',
            'post_title'  => $title !== '' ? $title : $slug,
        ];

        $is_update = $id > 0 && get_post($id);

        if ($is_update) {
            // Snapshot the outgoing tree before we replace it.
            $this->push_version((int) $id);
            $postarr['ID'] = $id;
            $result = wp_update_post($postarr, true);
        } else {
            $result = wp_insert_post($postarr, true);
        }

        if (is_wp_error($result)) {
            return 0;
        }
        $post_id = (int) $result;

        update_post_meta($post_id, '_acb_component_slug', $slug);
        update_post_meta($post_id, '_acb_layout', wp_slash(wp_json_encode($tree, JSON_UNESCAPED_SLASHES)));
        update_post_meta($post_id, '_acb_engine', (string) ($tree['layout_engine'] ?? 'bootstrap'));

        do_action('acb_flush_cache');
        return $post_id;
    }

    public function delete(int $id): bool
    {
        $post = get_post($id);
        if (!$post || $post->post_type !== Post_Types::TEMPLATE) {
            return false;
        }
        $ok = (bool) wp_delete_post($id, true);
        if ($ok) {
            do_action('acb_flush_cache');
        }
        return $ok;
    }

    public function duplicate(int $id): ?int
    {
        $record = $this->get($id);
        if ($record === null) {
            return null;
        }
        /* translators: %s: original template title. */
        $title = sprintf(__('%s (copy)', 'acf-component-builder'), $record['title']);
        $new   = $this->save(0, $title, $record['slug'], $record['tree']);
        return $new > 0 ? $new : null;
    }

    /* ------------------------------------------------------------------ */
    /* Versioning                                                         */
    /* ------------------------------------------------------------------ */

    private const MAX_VERSIONS = 20;

    /**
     * Snapshot the currently-stored tree onto the capped version stack.
     */
    private function push_version(int $id): void
    {
        $raw = get_post_meta($id, '_acb_layout', true);
        if (!is_string($raw) || $raw === '') {
            return;
        }
        $versions = $this->raw_versions($id);
        array_unshift($versions, [
            'time'   => time(),
            'author' => get_current_user_id(),
            'tree'   => $raw, // already-sanitised JSON string
        ]);
        $versions = array_slice($versions, 0, self::MAX_VERSIONS);
        update_post_meta($id, '_acb_versions', wp_slash(wp_json_encode($versions, JSON_UNESCAPED_SLASHES)));
    }

    /**
     * @return array<int,array{time:int,author:int,tree:string}>
     */
    private function raw_versions(int $id): array
    {
        $raw  = get_post_meta($id, '_acb_versions', true);
        $data = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];
        return is_array($data) ? $data : [];
    }

    /**
     * Version list for display (no tree payload).
     *
     * @return array<int,array{index:int,time:string,author:string}>
     */
    public function versions(int $id): array
    {
        $out = [];
        foreach ($this->raw_versions($id) as $i => $v) {
            $uid  = (int) ($v['author'] ?? 0);
            $user = $uid ? get_userdata($uid) : null;
            $out[] = [
                'index'  => (int) $i,
                'time'   => (string) wp_date('Y-m-d H:i', (int) ($v['time'] ?? 0)),
                'author' => $user ? $user->display_name : __('unknown', 'acf-component-builder'),
            ];
        }
        return $out;
    }

    /**
     * Restore a snapshot by index. The current tree is first snapshotted so a
     * restore is itself reversible. Returns the restored (sanitised) tree.
     *
     * @return array<string,mixed>|null
     */
    public function restore(int $id, int $index): ?array
    {
        $versions = $this->raw_versions($id);
        if (!isset($versions[$index])) {
            return null;
        }
        $tree = json_decode((string) $versions[$index]['tree'], true);
        if (!is_array($tree)) {
            return null;
        }
        $record = $this->get($id);
        $title  = $record['title'] ?? '';
        $slug   = $record['slug'] ?? '';

        // save() snapshots the outgoing (current) tree, keeping history intact.
        $this->save($id, $title, $slug, $tree);
        return Node_Sanitizer::tree($tree);
    }

    /* ------------------------------------------------------------------ */
    /* Import / Export                                                    */
    /* ------------------------------------------------------------------ */

    /**
     * Build a portable export envelope for a saved template.
     *
     * @return array<string,mixed>|null
     */
    public function export(int $id): ?array
    {
        $record = $this->get($id);
        if ($record === null) {
            return null;
        }
        return [
            'acb'     => 'template',
            'version' => ACB_VERSION,
            'title'   => $record['title'],
            'slug'    => $record['slug'],
            'tree'    => $record['tree'],
        ];
    }

    /**
     * Import a portable envelope, creating a new template. Returns the new ID
     * or null if the payload is not a valid template envelope.
     *
     * @param array<string,mixed> $payload
     */
    public function import(array $payload): ?int
    {
        if (($payload['acb'] ?? '') !== 'template' || !is_array($payload['tree'] ?? null)) {
            return null;
        }
        $title = (string) ($payload['title'] ?? __('Imported template', 'acf-component-builder'));
        $slug  = (string) ($payload['slug'] ?? '');
        if ($slug === '') {
            return null;
        }
        $new = $this->save(0, $title, $slug, (array) $payload['tree']);
        return $new > 0 ? $new : null;
    }
}
