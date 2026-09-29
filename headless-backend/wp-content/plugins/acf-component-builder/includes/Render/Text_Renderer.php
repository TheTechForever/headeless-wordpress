<?php

declare(strict_types=1);

namespace ACB\Render;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders text / textarea / number / email / url style scalar values into a
 * configurable HTML tag.
 *
 * @package ACFComponentBuilder
 */
final class Text_Renderer implements Field_Renderer_Interface
{
    private const ALLOWED = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'div', 'strong', 'em', 'small', 'label'];

    public function render(mixed $value, array $node): string
    {
        if ($value === null || $value === '' || is_array($value)) {
            return '';
        }
        $tag = Html::tag($node['tag'] ?? 'p', 'p', self::ALLOWED);

        $text = (string) $value;
        // textarea: preserve line breaks.
        if (($node['render'] ?? '') === 'textarea') {
            return '<' . $tag . Html::attrs($node) . '>' . nl2br(esc_html($text)) . '</' . $tag . '>';
        }
        return '<' . $tag . Html::attrs($node) . '>' . esc_html($text) . '</' . $tag . '>';
    }
}
