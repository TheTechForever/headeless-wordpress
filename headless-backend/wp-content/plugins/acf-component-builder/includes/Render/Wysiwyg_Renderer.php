<?php

declare(strict_types=1);

namespace ACB\Render;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WYSIWYG / rich text renderer. Uses wp_kses_post() for safe output.
 *
 * @package ACFComponentBuilder
 */
final class Wysiwyg_Renderer implements Field_Renderer_Interface
{
    public function render(mixed $value, array $node): string
    {
        if ($value === null || $value === '' || is_array($value)) {
            return '';
        }
        $html    = wp_kses_post((string) $value);
        $classes = Html::classes((string) ($node['class'] ?? 'acb-wysiwyg'));
        $attr    = Html::attrs(array_merge($node, ['class' => $classes]));
        return '<div' . $attr . '>' . $html . '</div>';
    }
}
