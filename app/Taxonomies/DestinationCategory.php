<?php

declare(strict_types=1);

namespace PWT\Taxonomies;

defined('ABSPATH') || exit;

/**
 * Destination Category taxonomy.
 */
final class DestinationCategory extends Taxonomy
{
    protected string $taxonomy = 'pwt_destination_category';

    protected string $singular = 'Destination Category';

    protected string $plural = 'Destination Categories';

    protected array $postTypes = [
        'pwt_destination',
    ];
}