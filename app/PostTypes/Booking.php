<?php

declare(strict_types=1);

namespace PWT\PostTypes;

defined('ABSPATH') || exit;

/**
 * Booking custom post type.
 */
final class Booking extends PostType
{
    /**
     * Post type slug.
     */
    protected string $postType = 'pwt_booking';

    /**
     * Singular label.
     */
    protected string $singular = 'Booking';

    /**
     * Plural label.
     */
    protected string $plural = 'Bookings';

    /**
     * Supported editor features.
     *
     * @var string[]
     */
    protected array $supports = [
        'title',
    ];

    /**
     * Menu icon.
     */
    protected string $menuIcon = 'dashicons-calendar-alt';

    /**
     * Menu position.
     */
    protected int $menuPosition = 25;

    /**
     * Whether the post type is public.
     */
    protected bool $public = false;

    /**
     * Whether REST API is enabled.
     */
    protected bool $showInRest = true;

    /**
     * Whether archives are enabled.
     */
    protected bool $hasArchive = false;

    /**
     * Rewrite slug.
     */
    protected ?string $rewriteSlug = null;
}