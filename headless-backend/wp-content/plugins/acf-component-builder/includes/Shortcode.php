<?php

declare(strict_types=1);

namespace ACB;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * [acf_component name="hero" template="" post_id="" class=""]
 *
 * @package ACFComponentBuilder
 */
final class Shortcode
{
    /**
     * @param array<string,string>|string $atts
     */
    public static function render($atts): string
    {
        $atts = shortcode_atts([
            'name'     => '',
            'flexible' => '',
            'template' => '',
            'post_id'  => '',
            'class'    => '',
        ], (array) $atts, 'acf_component');

        $args = [];
        if ($atts['post_id'] !== '') {
            $args['post_id'] = (int) $atts['post_id'];
        }
        if ($atts['class'] !== '') {
            $args['class'] = sanitize_text_field($atts['class']);
        }
        if ($atts['template'] !== '') {
            $args['template'] = sanitize_key($atts['template']);
        }

        if ($atts['flexible'] !== '') {
            return \ACB::render_flexible(sanitize_key($atts['flexible']), $args);
        }
        if ($atts['name'] !== '') {
            return \ACB::render(sanitize_key($atts['name']), $args);
        }
        return '';
    }
}
