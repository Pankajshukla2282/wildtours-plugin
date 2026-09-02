<?php

declare(strict_types=1);

namespace PWT\Taxonomies;

defined('ABSPATH') || exit;

/**
 * Season taxonomy.
 */
final class Season extends Taxonomy
{
    protected string $taxonomy = 'pwt_season';

    protected string $singular = 'Season';

    protected string $plural = 'Seasons';

    protected array $postTypes = [
        'pwt_package',
        'pwt_safari',
    ];
}