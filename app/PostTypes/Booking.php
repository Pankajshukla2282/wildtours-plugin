<?php

declare(strict_types=1);

namespace PWT\PostTypes;

defined('ABSPATH') || exit;

/**
 * Booking custom post type.
 *
 * Stores booking requests created through the booking form, the REST
 * endpoint or the admin. Internal only - not publicly queryable.
 */
final class Booking extends PostType
{
    protected string $postType = 'pwt_booking';

    protected string $singular = 'Booking';

    protected string $plural = 'Bookings';

    protected string $menuIcon = 'dashicons-calendar-alt';

    protected bool $public = false;

    protected bool $showInRest = false;

    protected bool $hasArchive = false;

    /**
     * Build post type arguments.
     */
    protected function args(): array
    {
        return [
            'labels'          => $this->labels(),
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => 'pwt-dashboard',
            'show_in_rest'    => false,
            'menu_position'   => $this->menuPosition,
            'menu_icon'       => $this->menuIcon,
            'supports'        => ['title'],
            'capability_type' => 'post',
            'has_archive'     => false,
            'exclude_from_search' => true,
            'rewrite'         => false,
        ];
    }
}
