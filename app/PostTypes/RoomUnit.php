<?php
declare(strict_types=1);
namespace PWT\PostTypes;
defined('ABSPATH') || exit;

final class RoomUnit extends PostType
{
    protected string $postType = 'pwt_room_unit';
    protected string $singular = 'Room Unit';
    protected string $plural = 'Room Units';
    protected string $menuIcon = 'dashicons-building';
    protected ?string $rewriteSlug = 'room-units';
    protected bool $hasArchive = false;
}
