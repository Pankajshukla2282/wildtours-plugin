<?php

namespace PWT\PostTypes;

defined('ABSPATH') || exit;

class Review extends PostType
{
    protected string $postType = 'pwt_review';

    protected string $singular = 'Review';

    protected string $plural = 'Reviews';

    protected function menuIcon(): string
    {
        return 'dashicons-star-filled';
    }
}
