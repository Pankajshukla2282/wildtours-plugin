<?php

namespace PWT\PostTypes;

defined('ABSPATH') || exit;

class FAQ extends PostType
{
    protected string $postType='pwt_faq';

    protected string $singular='FAQ';

    protected string $plural='FAQs';

    protected function menuIcon(): string
    {
        return 'dashicons-editor-help';
    }
}