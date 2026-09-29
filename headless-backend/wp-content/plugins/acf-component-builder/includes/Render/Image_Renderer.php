<?php

declare(strict_types=1);

namespace ACB\Render;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Image renderer. Handles ACF image return formats: array, id, url.
 *
 * @package ACFComponentBuilder
 */
final class Image_Renderer implements Field_Renderer_Interface
{
    public function render(mixed $value, array $node): string
    {
        $size = isset($node['size']) ? (string) $node['size'] : 'full';
        [$url, $alt] = $this->extract($value, $size);
        if ($url === '') {
            return '';
        }
        $classes = Html::classes((string) ($node['class'] ?? 'acb-image'));
        $attr    = Html::attrs(array_merge($node, ['class' => $classes]));
        return '<img src="' . esc_url($url) . '" alt="' . esc_attr($alt) . '" loading="lazy"' . $attr . ' />';
    }

    /**
     * @return array{0:string,1:string} [url, alt]
     */
    private function extract(mixed $value, string $size): array
    {
        if (is_array($value)) {
            $alt = (string) ($value['alt'] ?? '');
            if (!empty($value['sizes'][$size])) {
                return [(string) $value['sizes'][$size], $alt];
            }
            return [(string) ($value['url'] ?? ''), $alt];
        }
        if (is_numeric($value)) {
            $src = wp_get_attachment_image_src((int) $value, $size);
            $alt = (string) get_post_meta((int) $value, '_wp_attachment_image_alt', true);
            return [$src ? (string) $src[0] : '', $alt];
        }
        if (is_string($value)) {
            return [$value, ''];
        }
        return ['', ''];
    }
}
