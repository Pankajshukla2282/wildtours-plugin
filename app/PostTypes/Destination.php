<?php

namespace PWT\PostTypes;

defined('ABSPATH') || exit;

class Destination extends PostType
{
    protected string $postType='pwt_destination';

    protected string $singular='Destination';

    protected string $plural='Destinations';

    protected string $menuIcon = 'dashicons-location';
}