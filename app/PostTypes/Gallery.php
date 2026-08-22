<?php

namespace PWT\PostTypes;

defined('ABSPATH') || exit;

class Gallery extends PostType
{
    protected string $postType='pwt_gallery';

    protected string $singular='Gallery';

    protected string $plural='Galleries';

    protected string $menuIcon = 'dashicons-format-gallery';

    protected function args(): array
    {
        $args = parent::args();
        $args['show_in_menu'] = false;

        return $args;
    }
}