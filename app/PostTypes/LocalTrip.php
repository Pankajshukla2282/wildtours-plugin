<?php
declare(strict_types=1);
namespace PWT\PostTypes;
defined('ABSPATH') || exit;

final class LocalTrip extends PostType
{
    protected string $postType = 'pwt_local_trip';
    protected string $singular = 'Local Trip';
    protected string $plural = 'Local Trips';
    protected string $menuIcon = 'dashicons-location-alt';
    protected ?string $rewriteSlug = 'local-trips';
    protected array $taxonomies = ['pwt_trip_type', 'pwt_activity'];
}
