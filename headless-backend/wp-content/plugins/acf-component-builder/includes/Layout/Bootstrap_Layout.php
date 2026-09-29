<?php

declare(strict_types=1);

namespace ACB\Layout;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bootstrap 4/5 layout engine.
 *
 * @package ACFComponentBuilder
 */
final class Bootstrap_Layout implements Layout_Engine_Interface
{
    private const BREAKPOINTS = ['xs', 'sm', 'md', 'lg', 'xl', 'xxl'];

    public function __construct(private string $version = '5')
    {
    }

    public function id(): string
    {
        return 'bootstrap';
    }

    public function container_classes(array $settings): string
    {
        $container = (string) ($settings['container'] ?? 'container');
        $classes   = [$container !== '' ? $container : 'container'];
        return trim(implode(' ', $classes));
    }

    public function row_classes(array $settings): string
    {
        $classes = ['row'];

        // Gutters g-0 .. g-5.
        if (isset($settings['gutter']) && $settings['gutter'] !== '') {
            $g = preg_replace('/[^0-5]/', '', (string) $settings['gutter']);
            if ($g !== '') {
                $classes[] = 'g-' . $g;
            }
        }
        if (!empty($settings['justify'])) {
            $classes[] = 'justify-content-' . sanitize_html_class((string) $settings['justify']);
        }
        if (!empty($settings['align'])) {
            $classes[] = 'align-items-' . sanitize_html_class((string) $settings['align']);
        }
        return trim(implode(' ', $classes));
    }

    public function column_classes(array $settings): string
    {
        $classes = [];
        foreach (self::BREAKPOINTS as $bp) {
            if (!isset($settings[$bp]) || $settings[$bp] === '') {
                continue;
            }
            $val = $settings[$bp];
            $prefix = $bp === 'xs' ? 'col' : 'col-' . $bp;
            if ($val === 'auto') {
                $classes[] = $prefix;
            } else {
                $num = (int) $val;
                if ($num >= 1 && $num <= 12) {
                    $classes[] = $prefix . '-' . $num;
                }
            }
        }
        if (empty($classes)) {
            $classes[] = 'col';
        }

        // Offsets, order, alignment.
        foreach (self::BREAKPOINTS as $bp) {
            $okey = 'offset_' . $bp;
            if (isset($settings[$okey]) && $settings[$okey] !== '') {
                $n = (int) $settings[$okey];
                if ($n >= 0 && $n <= 11) {
                    $classes[] = $bp === 'xs' ? 'offset-' . $n : 'offset-' . $bp . '-' . $n;
                }
            }
        }
        if (!empty($settings['order'])) {
            $classes[] = 'order-' . sanitize_html_class((string) $settings['order']);
        }
        if (!empty($settings['align_self'])) {
            $classes[] = 'align-self-' . sanitize_html_class((string) $settings['align_self']);
        }
        return trim(implode(' ', $classes));
    }

    public function inline_style(array $settings): string
    {
        return '';
    }
}
