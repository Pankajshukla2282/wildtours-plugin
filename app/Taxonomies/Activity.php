<?php

declare(strict_types=1);

namespace PWT\Taxonomies;

defined('ABSPATH') || exit;

/**
 * Activity taxonomy.
 */
final class Activity extends Taxonomy
{
    protected string $taxonomy = 'pwt_activity';

    protected string $singular = 'Activity';

    protected string $plural = 'Activities';

    protected array $postTypes = [
        'pwt_package',
        'pwt_safari',
    ];
}