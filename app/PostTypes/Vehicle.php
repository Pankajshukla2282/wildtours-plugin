<?php

namespace PWT\PostTypes;

defined('ABSPATH') || exit;

class Vehicle extends PostType
{
    protected string $postType='pwt_vehicle';

    protected string $singular='Vehicle';

    protected string $plural='Vehicles';

    protected string $menuIcon = 'dashicons-car';

    protected function args(): array
    {
        $args = parent::args();
        $args['show_in_menu'] = false;

        return $args;
    }
}