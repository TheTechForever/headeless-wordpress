<?php

declare(strict_types=1);

namespace ACB\Render;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Contract for a single ACF field-type renderer.
 *
 * @package ACFComponentBuilder
 */
interface Field_Renderer_Interface
{
    /**
     * @param mixed                $value    Resolved ACF value.
     * @param array<string,mixed>  $node     Template node (tag, class, id, attrs...).
     * @return string Safe HTML.
     */
    public function render(mixed $value, array $node): string;
}
