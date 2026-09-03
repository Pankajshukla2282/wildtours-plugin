<?php

namespace PWT\PostTypes;

defined('ABSPATH') || exit;

class Testimonial extends PostType
{
    protected string $postType='pwt_testimonial';

    protected string $singular='Testimonial';

    protected string $plural='Testimonials';

    protected function menuIcon(): string
    {
        return 'dashicons-format-quote';
    }
}