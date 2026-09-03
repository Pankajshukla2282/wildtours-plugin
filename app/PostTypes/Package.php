<?php

namespace PWT\PostTypes;

defined('ABSPATH') || exit;

class Package extends PostType
{
    protected string $postType = 'pwt_package';

    protected string $singular = 'Package';

    protected string $plural = 'Packages';

    protected function menuIcon(): string
    {
        return 'dashicons-location-alt';
    }
}