<?php

declare(strict_types=1);

namespace PWT\Taxonomies;

defined('ABSPATH') || exit;

/**
 * Resort Category taxonomy.
 */
final class ResortCategory extends Taxonomy
{
    protected string $taxonomy = 'pwt_resort_category';

    protected string $singular = 'Resort Category';

    protected string $plural = 'Resort Categories';

    protected array $postTypes = [
        'pwt_resort',
    ];
}