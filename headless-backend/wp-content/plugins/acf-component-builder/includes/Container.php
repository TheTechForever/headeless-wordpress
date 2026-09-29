<?php

declare(strict_types=1);

namespace ACB;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ultra-light service locator so the helper API can reach booted services
 * without global variables scattered across the codebase.
 *
 * @package ACFComponentBuilder
 */
final class Container
{
    /** @var array<string,mixed> */
    private static array $services = [];

    public static function set(string $key, mixed $service): void
    {
        self::$services[$key] = $service;
    }

    public static function get(string $key): mixed
    {
        return self::$services[$key] ?? null;
    }

    public static function has(string $key): bool
    {
        return isset(self::$services[$key]);
    }
}
