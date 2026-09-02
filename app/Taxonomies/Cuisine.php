<?php
declare(strict_types=1);
namespace PWT\Taxonomies;
defined('ABSPATH') || exit;

final class Cuisine extends Taxonomy
{
    protected string $taxonomy = 'pwt_cuisine';
    protected string $singular = 'Cuisine';
    protected string $plural = 'Cuisines';
    protected array $postTypes = ['pwt_restaurant'];
    protected ?string $rewriteSlug = 'cuisine';
}
