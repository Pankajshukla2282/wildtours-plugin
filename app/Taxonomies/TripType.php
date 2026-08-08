<?php
declare(strict_types=1);
namespace PWT\Taxonomies;
defined('ABSPATH') || exit;

final class TripType extends Taxonomy
{
    protected string $taxonomy = 'pwt_trip_type';
    protected string $singular = 'Trip Type';
    protected string $plural = 'Trip Types';
    protected array $postTypes = ['pwt_local_trip'];
    protected ?string $rewriteSlug = 'trip-type';
}
