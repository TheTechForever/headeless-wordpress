<?php

declare(strict_types=1);

namespace ACB\Layout;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Resolves a layout-engine id to an engine instance. Extensible via the
 * `acb_layout_engines` filter.
 *
 * @package ACFComponentBuilder
 */
final class Layout_Factory
{
    /** @var array<string,Layout_Engine_Interface> */
    private static array $cache = [];

    public static function make(string $engine): Layout_Engine_Interface
    {
        $engine = $engine !== '' ? $engine : 'bootstrap';

        if (isset(self::$cache[$engine])) {
            return self::$cache[$engine];
        }

        /**
         * Filter the map of custom engine builders.
         *
         * @param array<string,callable> $builders id => callable():Layout_Engine_Interface
         */
        $builders = apply_filters('acb_layout_engines', []);
        if (isset($builders[$engine]) && is_callable($builders[$engine])) {
            $instance = $builders[$engine]();
            if ($instance instanceof Layout_Engine_Interface) {
                return self::$cache[$engine] = $instance;
            }
        }

        $settings = (array) get_option('acb_settings', []);
        $bs_ver   = (string) ($settings['bootstrap_version'] ?? '5');

        $instance = match ($engine) {
            'flex'  => new Flex_Layout(),
            'grid'  => new Grid_Layout(),
            default => new Bootstrap_Layout($bs_ver),
        };

        return self::$cache[$engine] = $instance;
    }
}
