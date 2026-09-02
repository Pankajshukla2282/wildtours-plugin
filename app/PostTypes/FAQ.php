<?php

namespace PWT\PostTypes;

defined('ABSPATH') || exit;

class FAQ extends PostType
{
    protected string $postType='pwt_faq';

    protected string $singular='FAQ';

    protected string $plural='FAQs';

    protected string $menuIcon = 'dashicons-editor-help';

    protected function args(): array
    {
        $args = parent::args();
        $args['show_in_menu'] = false;

        return $args;
    }
}