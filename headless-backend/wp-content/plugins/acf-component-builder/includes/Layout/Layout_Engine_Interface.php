<?php

declare(strict_types=1);

namespace ACB\Layout;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Contract every layout engine implements. Engines translate abstract layout
 * settings from a template node into concrete CSS classes / inline styles.
 *
 * @package ACFComponentBuilder
 */
interface Layout_Engine_Interface
{
    public function id(): string;

    /**
     * Classes for a container element.
     *
     * @param array<string,mixed> $settings
     * @return string
     */
    public function container_classes(array $settings): string;

    /**
     * Classes for a row / flex parent / grid parent.
     *
     * @param array<string,mixed> $settings
     */
    public function row_classes(array $settings): string;

    /**
     * Classes for a column / flex child / grid child.
     *
     * @param array<string,mixed> $settings
     */
    public function column_classes(array $settings): string;

    /**
     * Optional inline style string for a node (e.g. CSS Grid template).
     *
     * @param array<string,mixed> $settings
     */
    public function inline_style(array $settings): string;
}
