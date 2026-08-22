<?php

declare(strict_types=1);

namespace PWT\PostTypes;

defined('ABSPATH') || exit;

/**
 * Room Unit custom post type.
 *
 * Represents an individual room or inventory unit that can be
 * associated with a room type and resort.
 */
final class RoomUnit extends PostType
{
    /**
     * Post type slug.
     */
    protected string $postType = 'pwt_room_unit';

    /**
     * Singular label.
     */
    protected string $singular = 'Room Unit';

    /**
     * Plural label.
     */
    protected string $plural = 'Room Units';

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
    protected string $menuIcon = 'dashicons-admin-home';

    /**
     * Menu position.
     */
    protected int $menuPosition = 26;

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