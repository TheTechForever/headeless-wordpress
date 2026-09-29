<?php

declare(strict_types=1);

namespace ACB\Render;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Link / button renderer. Handles ACF link (array) and url (string) formats.
 *
 * @package ACFComponentBuilder
 */
final class Link_Renderer implements Field_Renderer_Interface
{
    public function render(mixed $value, array $node): string
    {
        $url = $title = $target = '';
        if (is_array($value)) {
            $url    = (string) ($value['url'] ?? '');
            $title  = (string) ($value['title'] ?? '');
            $target = (string) ($value['target'] ?? '');
        } elseif (is_string($value)) {
            $url = $value;
        }
        if ($url === '') {
            return '';
        }
        if ($title === '') {
            $title = (string) ($node['label'] ?? __('Read more', 'acf-component-builder'));
        }
        $classes = Html::classes((string) ($node['class'] ?? 'acb-link'));
        $attr    = Html::attrs(array_merge($node, ['class' => $classes]));
        $rel     = $target === '_blank' ? ' rel="noopener noreferrer"' : '';
        $tgt     = $target !== '' ? ' target="' . esc_attr($target) . '"' : '';
        return '<a href="' . esc_url($url) . '"' . $tgt . $rel . $attr . '>' . esc_html($title) . '</a>';
    }
}
