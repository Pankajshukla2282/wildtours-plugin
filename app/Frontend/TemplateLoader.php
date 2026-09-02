<?php

declare(strict_types=1);

namespace PWT\Frontend;

defined('ABSPATH') || exit;

use PWT\Core\Paths;

final class TemplateLoader
{
    public function register(): void
    {
        add_filter(
            'template_include',
            [$this, 'template'],
            99
        );
    }

    public function template(string $template): string
    {
        $resolved = $this->resolve($template);

        return apply_filters('pwt/template', $resolved);
    }

    private function resolve(string $template): string
    {
        if (is_singular('pwt_package')) {
            $themeTemplate = locate_template([
                'single-pwt_package.php',
                'template-parts/pwt/single-package.php',
            ]);

            if (is_string($themeTemplate) && $themeTemplate !== '') {
                return $themeTemplate;
            }

            $pluginTemplate = Paths::path('public/templates/single-package.php');
            if (file_exists($pluginTemplate)) {
                return $pluginTemplate;
            }
        }

        if (is_singular('pwt_safari')) {
            $themeTemplate = locate_template([
                'single-pwt_safari.php',
                'template-parts/pwt/single-safari.php',
            ]);

            if (is_string($themeTemplate) && $themeTemplate !== '') {
                return $themeTemplate;
            }

            $pluginTemplate = Paths::path('public/templates/single-safari.php');
            if (file_exists($pluginTemplate)) {
                return $pluginTemplate;
            }
        }

        $postType = get_query_var('post_type');
        $isPwtArchive = is_post_type_archive() && is_string($postType) && str_starts_with($postType, 'pwt_');

        $queriedObject = get_queried_object();
        $isPwtTaxonomy = is_tax()
            && $queriedObject instanceof \WP_Term
            && str_starts_with($queriedObject->taxonomy, 'pwt_');

        if ($isPwtArchive || $isPwtTaxonomy) {
            $candidates = ['template-parts/pwt/archive-listing.php', 'archive-listing.php'];

            if ($isPwtArchive && is_string($postType) && $postType !== '') {
                array_unshift($candidates, 'archive-' . $postType . '.php');
                array_unshift($candidates, 'template-parts/pwt/archive-' . $postType . '.php');
            }

            if ($isPwtTaxonomy && $queriedObject instanceof \WP_Term) {
                array_unshift($candidates, 'taxonomy-' . $queriedObject->taxonomy . '.php');
            }

            $themeTemplate = locate_template($candidates);
            if (is_string($themeTemplate) && $themeTemplate !== '') {
                return $themeTemplate;
            }

            $pluginTemplate = Paths::path('public/templates/archive-listing.php');
            if (file_exists($pluginTemplate)) {
                return $pluginTemplate;
            }
        }

        return $template;
    }
}