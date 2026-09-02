<?php

declare(strict_types=1);

namespace PWT\Taxonomies;

defined('ABSPATH') || exit;

/**
 * Package Category taxonomy.
 */
final class PackageCategory extends Taxonomy
{
    protected string $taxonomy = 'pwt_package_category';

    protected string $singular = 'Package Category';

    protected string $plural = 'Package Categories';

    protected array $postTypes = [
        'pwt_package',
    ];
}