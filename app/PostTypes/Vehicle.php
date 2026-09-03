<?php

namespace PWT\PostTypes;

defined('ABSPATH') || exit;

class Vehicle extends PostType
{
    protected string $postType='pwt_vehicle';

    protected string $singular='Vehicle';

    protected string $plural='Vehicles';

    protected function menuIcon(): string
    {
        return 'dashicons-car';
    }
}