<?php

declare(strict_types=1);

namespace PWT\Taxonomies\Contracts;

defined('ABSPATH') || exit;

/**
 * Contract for all taxonomy classes.
 */
interface TaxonomyInterface
{
    /**
     * Register taxonomy.
     */
    public function register(): void;
}