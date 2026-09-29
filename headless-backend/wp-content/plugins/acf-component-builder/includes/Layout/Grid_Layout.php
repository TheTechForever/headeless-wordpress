<?php

declare(strict_types=1);

namespace ACB\Layout;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * CSS Grid layout engine. Responsive column counts are emitted as inline
 * custom properties consumed by frontend.css media queries.
 *
 * @package ACFComponentBuilder
 */
final class Grid_Layout implements Layout_Engine_Interface
{
    public function id(): string
    {
        return 'grid';
    }

    public function container_classes(array $settings): string
    {
        return 'acb-container';
    }

    public function row_classes(array $settings): string
    {
        return 'acb-grid';
    }

    public function column_classes(array $settings): string
    {
        return 'acb-grid-item';
    }

    public function inline_style(array $settings): string
    {
        $styles = [];

        $desktop = (int) ($settings['columns'] ?? $settings['desktop'] ?? 0);
        $tablet  = (int) ($settings['tablet'] ?? 0);
        $mobile  = (int) ($settings['mobile'] ?? 0);

        if ($desktop > 0) {
            $styles['grid-template-columns'] = 'repeat(' . $desktop . ', 1fr)';
            $styles['--acb-grid-cols']        = (string) $desktop;
        }
        if ($tablet > 0) {
            $styles['--acb-grid-cols-tablet'] = (string) $tablet;
        }
        if ($mobile > 0) {
            $styles['--acb-grid-cols-mobile'] = (string) $mobile;
        }

        if (isset($settings['gap']) && $settings['gap'] !== '') {
            $styles['gap'] = $this->size((string) $settings['gap']);
        }
        if (isset($settings['column_gap']) && $settings['column_gap'] !== '') {
            $styles['column-gap'] = $this->size((string) $settings['column_gap']);
        }
        if (isset($settings['row_gap']) && $settings['row_gap'] !== '') {
            $styles['row-gap'] = $this->size((string) $settings['row_gap']);
        }
        if (!empty($settings['align'])) {
            $styles['align-items'] = sanitize_text_field((string) $settings['align']);
        }

        $out = '';
        foreach ($styles as $k => $v) {
            $out .= $k . ':' . $v . ';';
        }
        return $out;
    }

    private function size(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^\d+(\.\d+)?$/', $value)) {
            return $value . 'px';
        }
        if (preg_match('/^\d+(\.\d+)?(px|rem|em|%|vw|vh)$/', $value)) {
            return $value;
        }
        return '0';
    }
}
