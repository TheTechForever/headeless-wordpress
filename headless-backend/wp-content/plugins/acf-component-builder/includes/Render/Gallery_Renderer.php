<?php

declare(strict_types=1);

namespace ACB\Render;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gallery renderer. Expects an array of image arrays or attachment IDs.
 *
 * @package ACFComponentBuilder
 */
final class Gallery_Renderer implements Field_Renderer_Interface
{
    public function render(mixed $value, array $node): string
    {
        if (!is_array($value) || $value === []) {
            return '';
        }
        $size    = isset($node['size']) ? (string) $node['size'] : 'medium';
        $classes = Html::classes((string) ($node['class'] ?? 'acb-gallery'));
        $img     = new Image_Renderer();
        $out     = '<ul class="' . esc_attr($classes) . '">';
        foreach ($value as $item) {
            $html = $img->render($item, ['size' => $size, 'class' => 'acb-gallery__img']);
            if ($html !== '') {
                $out .= '<li class="acb-gallery__item">' . $html . '</li>';
            }
        }
        $out .= '</ul>';
        return $out;
    }
}
