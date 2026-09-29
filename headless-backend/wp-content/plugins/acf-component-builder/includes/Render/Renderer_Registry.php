<?php

declare(strict_types=1);

namespace ACB\Render;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Maps a "render" hint (or ACF field type) to a Field_Renderer_Interface.
 * Extensible through the `acb_field_renderers` filter.
 *
 * @package ACFComponentBuilder
 */
final class Renderer_Registry
{
    /** @var array<string,Field_Renderer_Interface> */
    private array $renderers = [];

    public function __construct()
    {
        $this->renderers = [
            'text'     => new Text_Renderer(),
            'textarea' => new Text_Renderer(),
            'number'   => new Text_Renderer(),
            'email'    => new Text_Renderer(),
            'heading'  => new Heading_Renderer(),
            'wysiwyg'  => new Wysiwyg_Renderer(),
            'image'    => new Image_Renderer(),
            'link'     => new Link_Renderer(),
            'url'      => new Link_Renderer(),
            'gallery'  => new Gallery_Renderer(),
        ];

        /**
         * Filter registered field renderers.
         *
         * @param array<string,Field_Renderer_Interface> $renderers
         */
        $this->renderers = apply_filters('acb_field_renderers', $this->renderers);
    }

    public function for(string $type): Field_Renderer_Interface
    {
        return $this->renderers[$type] ?? $this->renderers['text'];
    }

    public function has(string $type): bool
    {
        return isset($this->renderers[$type]);
    }
}
