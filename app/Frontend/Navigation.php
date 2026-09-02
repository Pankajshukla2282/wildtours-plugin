<?php

declare(strict_types=1);

namespace PWT\Frontend;

defined('ABSPATH') || exit;

/**
 * Reads the navigation tree from the SCF "PWT Navigation" options page.
 *
 * Reading order:
 *  1. get_field()/scf_get_field() on the 'option' id (SCF/ACF native).
 *  2. Raw SCF options-page storage (options_nav_items_...).
 *  3. The pwt_nav_items option (simple nested array).
 *
 * Falls back to WP Menus (wp_nav_menu) in the theme when no SCF tree exists.
 */
final class Navigation
{
    /**
     * Normalized navigation tree.
     *
     * @return array<int, array{label:string, url:string, mega:bool, children:array}>
     */
    public static function items(): array
    {
        $rows = self::rawItems();

        if ($rows === []) {
            return [];
        }

        $items = array_values(
            array_filter(
                array_map([self::class, 'normalizeItem'], $rows),
                static fn ($item) => $item !== null
            )
        );

        return $items;
    }

    /**
     * Header CTA (label + url), preferring SCF options with theme mod fallback.
     *
     * @return array{label:string, url:string}
     */
    public static function headerCta(): array
    {
        $label = (string) self::option('header_cta_label', '');
        $url = (string) self::option('header_cta_url', '');

        if ($label === '' && $url === '') {
            $label = (string) get_theme_mod('header_cta_label', '');
            $url = (string) get_theme_mod('header_cta_url', '');
        }

        return [
            'label' => $label,
            'url' => $url,
        ];
    }

    /**
     * Top bar text, preferring SCF options with theme mod fallback.
     */
    public static function topbarText(): string
    {
        $text = (string) self::option('topbar_text', '');

        if ($text !== '') {
            return $text;
        }

        return (string) get_theme_mod('topbar_text', '');
    }

    /**
     * Render the navigation tree as a nested UL/LI list using the same
     * class names WordPress menus output (menu-item, sub-menu, ...) so the
     * existing theme CSS keeps working.
     *
     * @param array<int, array{label:string, url:string, mega:bool, children:array}> $items
     */
    public static function renderMenu(array $items): string
    {
        $currentPath = untrailingslashit((string) wp_parse_url((string) add_query_arg([]), PHP_URL_PATH)) ?: '/';

        [$html] = self::renderLevel($items, 'primary-menu', $currentPath, 'primary-menu');

        return $html;
    }

    /**
     * @param array<int, array{label:string, url:string, mega:bool, children:array}> $items
     *
     * @return array{0:string, 1:bool} [html, hasCurrentItem]
     */
    private static function renderLevel(array $items, string $class, string $currentPath, string $id = ''): array
    {
        $html = '<ul'
            . ($class !== '' ? ' class="' . esc_attr($class) . '"' : '')
            . ($id !== '' ? ' id="' . esc_attr($id) . '"' : '')
            . '>';
        $hasCurrent = false;

        foreach ($items as $item) {
            $label = (string) ($item['label'] ?? '');
            $url = (string) ($item['url'] ?? '');
            $children = (array) ($item['children'] ?? []);

            $itemPath = untrailingslashit((string) wp_parse_url($url, PHP_URL_PATH)) ?: '/';
            $itemCurrent = $itemPath === $currentPath;

            $subHtml = '';
            $subHasCurrent = false;

            if ($children !== []) {
                [$subHtml, $subHasCurrent] = self::renderLevel($children, 'sub-menu', $currentPath);
            }

            $classes = ['menu-item'];

            if ($children !== []) {
                $classes[] = 'menu-item-has-children';
            }

            if ($itemCurrent) {
                $classes[] = 'current-menu-item';
            }

            if ($subHasCurrent) {
                $classes[] = 'current-menu-ancestor';
            }

            if (!empty($item['mega'])) {
                $classes[] = 'menu-mega';
            }

            $html .= '<li class="' . esc_attr(implode(' ', $classes)) . '">';
            $html .= '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
            $html .= $subHtml;
            $html .= '</li>';

            if ($itemCurrent || $subHasCurrent) {
                $hasCurrent = true;
            }
        }

        $html .= '</ul>';

        return [$html, $hasCurrent];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function rawItems(): array
    {
        if (function_exists('get_field')) {
            $value = get_field('nav_items', 'option');

            if (is_array($value) && $value !== []) {
                return $value;
            }
        }

        if (function_exists('scf_get_field')) {
            $value = scf_get_field('nav_items', 'option');

            if (is_array($value) && $value !== []) {
                return $value;
            }
        }

        $option = get_option('pwt_nav_items', []);

        if (is_array($option) && $option !== []) {
            return $option;
        }

        return self::readScfOptionFormat();
    }

    /**
     * Read the raw SCF/ACF options-page storage format directly.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function readScfOptionFormat(): array
    {
        $count = (int) get_option('options_nav_items', 0);

        if ($count < 1) {
            return [];
        }

        $rows = [];

        for ($index = 0; $index < $count; $index++) {
            $row = [
                'label' => (string) get_option("options_nav_items_{$index}_label", ''),
                'type' => (string) get_option("options_nav_items_{$index}_type", 'custom'),
                'slug' => (string) get_option("options_nav_items_{$index}_slug", ''),
                'url' => (string) get_option("options_nav_items_{$index}_url", ''),
                'mega' => (string) get_option("options_nav_items_{$index}_mega", ''),
            ];

            $childCount = (int) get_option("options_nav_items_{$index}_children", 0);

            if ($childCount > 0) {
                $children = [];

                for ($childIndex = 0; $childIndex < $childCount; $childIndex++) {
                    $children[] = [
                        'label' => (string) get_option("options_nav_items_{$index}_children_{$childIndex}_label", ''),
                        'type' => (string) get_option("options_nav_items_{$index}_children_{$childIndex}_type", 'custom'),
                        'slug' => (string) get_option("options_nav_items_{$index}_children_{$childIndex}_slug", ''),
                        'url' => (string) get_option("options_nav_items_{$index}_children_{$childIndex}_url", ''),
                    ];
                }

                $row['children'] = $children;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param mixed $default
     *
     * @return mixed
     */
    private static function option(string $name, $default)
    {
        if (function_exists('get_field')) {
            $value = get_field($name, 'option');

            if ($value !== null && $value !== false && $value !== '') {
                return $value;
            }
        }

        if (function_exists('scf_get_field')) {
            $value = scf_get_field($name, 'option');

            if ($value !== null && $value !== false && $value !== '') {
                return $value;
            }
        }

        $raw = get_option('options_' . $name, $default);

        if ($raw !== '' && $raw !== null && $raw !== false) {
            return $raw;
        }

        return $default;
    }

    /**
     * @param array<string, mixed> $item
     *
     * @return array{label:string, url:string, mega:bool, children:array}|null
     */
    private static function normalizeItem(array $item): ?array
    {
        $label = sanitize_text_field((string) ($item['label'] ?? ''));

        if ($label === '') {
            return null;
        }

        $type = (string) ($item['type'] ?? 'custom');
        $slug = (string) ($item['slug'] ?? '');
        $url = (string) ($item['url'] ?? '');

        if ($url === '') {
            $url = self::resolveUrl($slug, $type);
        }

        if ($url === '') {
            return null;
        }

        $children = [];

        foreach ((array) ($item['children'] ?? []) as $child) {
            if (is_array($child)) {
                $normalized = self::normalizeItem($child);

                if ($normalized !== null) {
                    $children[] = $normalized;
                }
            }
        }

        return [
            'label' => $label,
            'url' => $url,
            'mega' => !empty($item['mega']),
            'children' => $children,
        ];
    }

    private static function resolveUrl(string $slug, string $type): string
    {
        if ($slug === 'home') {
            return home_url('/');
        }

        if ($slug === '' || $type === 'custom') {
            return '';
        }

        $post = get_page_by_path($slug, OBJECT, $type === 'post' ? 'post' : 'page');

        return $post instanceof \WP_Post ? (string) get_permalink($post) : '';
    }
}