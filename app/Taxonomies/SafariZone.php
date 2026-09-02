<?php

declare(strict_types=1);

namespace PWT\Taxonomies;

defined('ABSPATH') || exit;

/**
 * Safari Zone taxonomy.
 */
final class SafariZone extends Taxonomy
{
    protected string $taxonomy = 'pwt_safari_zone';

    protected string $singular = 'Safari Zone';

    protected string $plural = 'Safari Zones';

    protected array $postTypes = [
        'pwt_safari',
        'pwt_safari_schedule',
    ];
}