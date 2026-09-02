<?php

namespace PWT\PostTypes;

defined('ABSPATH') || exit;

class Review extends PostType
{
    protected string $postType = 'pwt_review';

    protected string $singular = 'Review';

    protected string $plural = 'Reviews';

    protected string $menuIcon = 'dashicons-star-filled';
}
