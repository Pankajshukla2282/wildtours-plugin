<?php
declare(strict_types=1);
namespace PWT\PostTypes;
defined('ABSPATH') || exit;

final class Restaurant extends PostType
{
    protected string $postType = 'pwt_restaurant';
    protected string $singular = 'Restaurant';
    protected string $plural = 'Restaurants';
    protected string $menuIcon = 'dashicons-food';
    protected ?string $rewriteSlug = 'restaurants';
    protected array $taxonomies = ['pwt_cuisine'];
}
