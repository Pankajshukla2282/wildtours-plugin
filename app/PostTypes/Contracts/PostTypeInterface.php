<?php

declare(strict_types=1);

namespace PWT\PostTypes\Contracts;

defined('ABSPATH') || exit;

interface PostTypeInterface
{
    /**
     * Register the custom post type.
     */
    public function register(): void;
}