<?php

namespace PWT\Frontend;

defined('ABSPATH') || exit;

class Seo
{
    public function register(): void
    {
        add_action('wp_head', [$this, 'renderHeadTags'], 5);
    }

    public function renderHeadTags(): void
    {
        if (!is_singular(['pwt_package', 'pwt_safari', 'pwt_destination'])) {
            return;
        }

        $postId = get_queried_object_id();

        if (!$postId) {
            return;
        }

        $title = get_the_title($postId);
        $description = wp_strip_all_tags(get_the_excerpt($postId) ?: wp_trim_words((string) get_post_field('post_content', $postId), 35));
        $url = get_permalink($postId);
        $image = get_the_post_thumbnail_url($postId, 'large') ?: '';

        echo "\n";
        echo '<meta property="og:type" content="article">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";

        if ($image) {
            echo '<meta property="og:image" content="' . esc_url($image) . '">' . "\n";
        }

        $structuredData = $this->structuredData($postId, $title, $description, $url, $image);

        echo '<script type="application/ld+json">' . wp_json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }

    private function structuredData(int $postId, string $title, string $description, string $url, string $image): array
    {
        $settings = get_option('pwt_settings', []);
        $provider = $settings['company_name'] ?? get_bloginfo('name');

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'TouristTrip',
            'name' => $title,
            'description' => $description,
            'url' => $url,
            'provider' => [
                '@type' => 'TravelAgency',
                'name' => $provider,
            ],
        ];

        if ($image) {
            $data['image'] = $image;
        }

        $price = (float) get_post_meta($postId, 'offer_price', true);

        if ($price <= 0) {
            $price = (float) get_post_meta($postId, 'regular_price', true);
        }

        if ($price > 0) {
            $data['offers'] = [
                '@type' => 'Offer',
                'price' => (string) $price,
                'priceCurrency' => 'INR',
                'availability' => 'https://schema.org/InStock',
            ];
        }

        return $data;
    }
}
