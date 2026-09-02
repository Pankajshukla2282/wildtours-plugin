<?php

declare(strict_types=1);

namespace PWT\Core;

defined('ABSPATH') || exit;

/**
 * Plugin deactivation handler.
 */
final class Deactivator
{
    /**
     * Deactivate plugin.
     */
    public static function deactivate(): void
    {
        flush_rewrite_rules();
    }
}