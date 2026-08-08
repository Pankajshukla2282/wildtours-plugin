<?php
declare(strict_types=1);
namespace PWT\PostTypes;
defined('ABSPATH') || exit;

final class RoomType extends PostType
{
    protected string $postType = 'pwt_room_type';
    protected string $singular = 'Room Type';
    protected string $plural = 'Room Types';
    protected string $menuIcon = 'dashicons-admin-home';
    protected ?string $rewriteSlug = 'room-types';
    protected array $taxonomies = ['pwt_resort_category'];
}
