<?php

declare(strict_types=1);

namespace ACB\Layout;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * CSS Flexbox layout engine. Emits ACB-prefixed utility classes plus inline
 * styles so it works without Bootstrap being present.
 *
 * @package ACFComponentBuilder
 */
final class Flex_Layout implements Layout_Engine_Interface
{
    public function id(): string
    {
        return 'flex';
    }

    public function container_classes(array $settings): string
    {
        return 'acb-container';
    }

    public function row_classes(array $settings): string
    {
        return 'acb-flex';
    }

    public function column_classes(array $settings): string
    {
        return 'acb-flex-item';
    }

    public function inline_style(array $settings): string
    {
        $styles = [];

        // Container/row-level flex properties.
        if (!empty($settings['direction'])) {
            $styles['flex-direction'] = $this->safe($settings['direction'], ['row', 'column', 'row-reverse', 'column-reverse']);
        }
        if (!empty($settings['wrap'])) {
            $styles['flex-wrap'] = $this->safe($settings['wrap'], ['wrap', 'nowrap', 'wrap-reverse']);
        }
        if (!empty($settings['justify'])) {
            $styles['justify-content'] = $this->safe(
                $settings['justify'],
                ['flex-start', 'flex-end', 'center', 'space-between', 'space-around', 'space-evenly']
            );
        }
        if (!empty($settings['align'])) {
            $styles['align-items'] = $this->safe(
                $settings['align'],
                ['flex-start', 'flex-end', 'center', 'stretch', 'baseline']
            );
        }
        if (isset($settings['gap']) && $settings['gap'] !== '') {
            $styles['gap'] = $this->size((string) $settings['gap']);
        }

        // Item-level.
        if (isset($settings['grow']) && $settings['grow'] !== '') {
            $styles['flex-grow'] = (string) (int) $settings['grow'];
        }
        if (isset($settings['basis']) && $settings['basis'] !== '') {
            $styles['flex-basis'] = $this->size((string) $settings['basis']);
        }

        return $this->compile($styles);
    }

    /** @param list<string> $allowed */
    private function safe(mixed $value, array $allowed): string
    {
        $value = (string) $value;
        return in_array($value, $allowed, true) ? $value : $allowed[0];
    }

    private function size(string $value): string
    {
        // Allow "16px", "1rem", "2", "50%". Bare numbers become px.
        $value = trim($value);
        if (preg_match('/^\d+(\.\d+)?$/', $value)) {
            return $value . 'px';
        }
        if (preg_match('/^\d+(\.\d+)?(px|rem|em|%|vw|vh)$/', $value)) {
            return $value;
        }
        return '0';
    }

    /** @param array<string,string> $styles */
    private function compile(array $styles): string
    {
        $out = '';
        foreach ($styles as $prop => $val) {
            $out .= $prop . ':' . $val . ';';
        }
        return $out;
    }
}
