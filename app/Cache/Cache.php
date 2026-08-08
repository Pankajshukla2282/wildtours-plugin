<?php

declare(strict_types=1);

namespace PWT\Cache;

defined('ABSPATH') || exit;

class Cache
{
    public function remember(
        string $key,
        int $expiration,
        callable $callback
    ): mixed {

        $cached = get_transient($key);

        if ($cached !== false) {
            return $cached;
        }

        $value = $callback();

        set_transient(
            $key,
            $value,
            $expiration
        );

        return $value;
    }
}