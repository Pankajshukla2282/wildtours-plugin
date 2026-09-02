<?php

declare(strict_types=1);

namespace PWT\Taxonomies;

defined('ABSPATH') || exit;

/**
 * Vehicle Type taxonomy.
 */
final class VehicleType extends Taxonomy
{
    protected string $taxonomy = 'pwt_vehicle_type';

    protected string $singular = 'Vehicle Type';

    protected string $plural = 'Vehicle Types';

    protected array $postTypes = [
        'pwt_vehicle',
    ];
}