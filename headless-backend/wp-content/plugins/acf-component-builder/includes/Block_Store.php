<?php

declare(strict_types=1);

namespace ACB;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Store for the reusable block library (CPT {@see Post_Types::BLOCK}).
 *
 * A block is a saved node subtree that can be inserted into any template — the
 * building block of the "Component Library". Each block stores:
 *   - post_title            : label
 *   - meta _acb_block_tree   : sanitised node subtree (JSON)
 *   - meta _acb_block_cat    : optional category for grouping
 *   - meta _acb_block_desc   : optional description
 *
 * @package ACFComponentBuilder
 */
final class Block_Store
{
    /**
     * @return array<int,array{id:int,title:string,category:string,description:string,type:string}>
     */
    public function all(): array
    {
        $posts = get_posts([
            'post_type'        => Post_Types::BLOCK,
            'post_status'      => ['publish', 'draft'],
            'numberposts'      => 300,
            'orderby'          => 'title',
            'order'            => 'ASC',
            'suppress_filters' => false,
        ]);

        $rows = [];
        foreach ($posts as $p) {
            $tree = $this->tree($p->ID);
            $rows[] = [
                'id'          => (int) $p->ID,
                'title'       => $p->post_title !== '' ? $p->post_title : __('(untitled block)', 'acf-component-builder'),
                'category'    => (string) get_post_meta($p->ID, '_acb_block_cat', true),
                'description' => (string) get_post_meta($p->ID, '_acb_block_desc', true),
                'type'        => (string) ($tree['type'] ?? 'row'),
            ];
        }
        return $rows;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function tree(int $id): ?array
    {
        $post = get_post($id);
        if (!$post || $post->post_type !== Post_Types::BLOCK) {
            return null;
        }
        $raw  = get_post_meta($id, '_acb_block_tree', true);
        $node = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
        if (!is_array($node)) {
            return null;
        }
        return Node_Sanitizer::subtree($node);
    }

    /**
     * Persist a block. Returns the post ID (0 on failure / invalid node).
     *
     * @param array<string,mixed> $node Raw node subtree from the builder.
     */
    public function save(int $id, string $title, string $category, string $description, array $node): int
    {
        $clean = Node_Sanitizer::subtree($node);
        if ($clean === null) {
            return 0;
        }

        $postarr = [
            'post_type'   => Post_Types::BLOCK,
            'post_status' => 'publish',
            'post_title'  => sanitize_text_field($title) ?: __('Block', 'acf-component-builder'),
        ];
        if ($id > 0 && get_post($id)) {
            $postarr['ID'] = $id;
            $result = wp_update_post($postarr, true);
        } else {
            $result = wp_insert_post($postarr, true);
        }
        if (is_wp_error($result)) {
            return 0;
        }
        $post_id = (int) $result;

        update_post_meta($post_id, '_acb_block_tree', wp_slash(wp_json_encode($clean, JSON_UNESCAPED_SLASHES)));
        update_post_meta($post_id, '_acb_block_cat', sanitize_text_field($category));
        update_post_meta($post_id, '_acb_block_desc', sanitize_textarea_field($description));

        return $post_id;
    }

    public function delete(int $id): bool
    {
        $post = get_post($id);
        if (!$post || $post->post_type !== Post_Types::BLOCK) {
            return false;
        }
        return (bool) wp_delete_post($id, true);
    }
}
