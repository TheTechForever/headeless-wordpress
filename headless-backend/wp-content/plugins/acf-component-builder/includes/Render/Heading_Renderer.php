<?php

declare(strict_types=1);

namespace ACB\Render;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Heading renderer (defaults to h2).
 *
 * @package ACFComponentBuilder
 */
final class Heading_Renderer implements Field_Renderer_Interface
{
    private const ALLOWED = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

    public function render(mixed $value, array $node): string
    {
        if ($value === null || $value === '' || is_array($value)) {
            return '';
        }
        $tag = Html::tag($node['tag'] ?? 'h2', 'h2', self::ALLOWED);
        return '<' . $tag . Html::attrs($node) . '>' . esc_html((string) $value) . '</' . $tag . '>';
    }
}
