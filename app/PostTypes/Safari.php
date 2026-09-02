<?php

declare(strict_types=1);

namespace PWT\PostTypes;

defined('ABSPATH') || exit;

/**
 * Safari custom post type.
 */
final class Safari extends PostType
{
    protected string $postType = 'pwt_safari';

    protected string $singular = 'Safari';

    protected string $plural = 'Safaris';

    protected string $menuIcon = 'dashicons-camera';
}