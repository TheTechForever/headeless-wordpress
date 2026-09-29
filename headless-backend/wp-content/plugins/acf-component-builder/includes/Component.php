<?php

declare(strict_types=1);

namespace ACB;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Immutable-ish value object describing one component.
 *
 * A component maps to either:
 *  - a whole ACF Flexible Content field (with N layouts), or
 *  - a single flexible-content layout, or
 *  - a stand-alone group of fields.
 *
 * @package ACFComponentBuilder
 */
final class Component
{
    /**
     * @param string                          $slug          Machine slug, e.g. "services-inner-detail-section".
     * @param string                          $name          Human label.
     * @param string                          $source_type   "flexible_content" | "layout" | "group".
     * @param string                          $field_name    ACF field/layout name it maps to.
     * @param string                          $field_key     ACF field/layout key.
     * @param string                          $group_key     Owning ACF field group key.
     * @param array<int,array<string,mixed>>  $fields        Normalised field definitions.
     * @param array<string,mixed>             $meta          Extra data (layouts list, default classes...).
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $name,
        public readonly string $source_type,
        public readonly string $field_name,
        public readonly string $field_key,
        public readonly string $group_key,
        public readonly array $fields = [],
        public readonly array $meta = []
    ) {
    }

    public function is_flexible(): bool
    {
        return $this->source_type === 'flexible_content';
    }

    /**
     * @return array<string,mixed>
     */
    public function to_array(): array
    {
        return [
            'slug'        => $this->slug,
            'name'        => $this->name,
            'source_type' => $this->source_type,
            'field_name'  => $this->field_name,
            'field_key'   => $this->field_key,
            'group_key'   => $this->group_key,
            'fields'      => $this->fields,
            'meta'        => $this->meta,
        ];
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function from_array(array $data): self
    {
        return new self(
            (string) ($data['slug'] ?? ''),
            (string) ($data['name'] ?? ''),
            (string) ($data['source_type'] ?? 'group'),
            (string) ($data['field_name'] ?? ''),
            (string) ($data['field_key'] ?? ''),
            (string) ($data['group_key'] ?? ''),
            (array) ($data['fields'] ?? []),
            (array) ($data['meta'] ?? [])
        );
    }
}
