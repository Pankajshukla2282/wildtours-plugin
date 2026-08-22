<?php

declare(strict_types=1);

namespace PWT\PostTypes;

defined('ABSPATH') || exit;

/**
 * Safari Schedule custom post type.
 */
final class SafariSchedule extends PostType
{
    /**
     * Post type slug.
     */
    protected string $postType = 'pwt_safari_schedule';

    /**
     * Singular label.
     */
    protected string $singular = 'Safari Schedule';

    /**
     * Plural label.
     */
    protected string $plural = 'Safari Schedules';

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
    protected string $menuIcon = 'dashicons-location-alt';

    /**
     * Menu position.
     */
    protected int $menuPosition = 27;

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