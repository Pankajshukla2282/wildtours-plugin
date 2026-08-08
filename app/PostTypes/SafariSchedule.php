<?php
declare(strict_types=1);
namespace PWT\PostTypes;
defined('ABSPATH') || exit;

final class SafariSchedule extends PostType
{
    protected string $postType = 'pwt_safari_schedule';
    protected string $singular = 'Safari Schedule';
    protected string $plural = 'Safari Schedules';
    protected string $menuIcon = 'dashicons-calendar-alt';
    protected ?string $rewriteSlug = 'safari-schedules';
    protected bool $hasArchive = false;
    protected array $taxonomies = ['pwt_safari_zone'];
}
