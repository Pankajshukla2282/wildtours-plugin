<?php

namespace PWT\PostTypes;

defined('ABSPATH') || exit;

class FAQ extends PostType
{
    protected string $postType='pwt_faq';

    protected string $singular='FAQ';

    protected string $plural='FAQs';

    protected string $menuIcon = 'dashicons-editor-help';
}