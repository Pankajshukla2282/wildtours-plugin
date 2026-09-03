<?php

declare(strict_types=1);

namespace PWT\Admin;

defined('ABSPATH') || exit;

/**
 * Imports starter content for faster website launch.
 */
final class ContentSeeder
{
    /**
     * @var string[]
     */
    private const SUPPORTED_TYPES = [
        'pwt_destination',
        'pwt_safari',
        'pwt_package',
        'pwt_faq',
        'pwt_testimonial',
        'pwt_review',
        'pwt_resort',
        'pwt_vehicle',
    ];

    public function register(): void
    {
        add_action('admin_post_pwt_seed_starter_content', [$this, 'handle']);
    }

    public function handle(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to perform this action.', 'wildtours-plugin'));
        }

        check_admin_referer('pwt_seed_starter_content');

        $profile = sanitize_key($_POST['seed_profile'] ?? 'basic');
        if (!in_array($profile, ['basic', 'full'], true)) {
            $profile = 'basic';
        }

        $enabledTypes = array_values(array_filter(array_map(
            'sanitize_key',
            (array) ($_POST['seed_types'] ?? self::SUPPORTED_TYPES)
        )));
        $enabledTypes = array_values(array_intersect(self::SUPPORTED_TYPES, $enabledTypes));

        $featuredMediaId = absint($_POST['seed_featured_media'] ?? 0);
        $useLatestMedia = sanitize_key((string) ($_POST['seed_use_latest_media'] ?? '')) === '1';

        if ($featuredMediaId <= 0 && $useLatestMedia) {
            $featuredMediaId = $this->latestAttachmentId();
        }

        $summary = $this->importStarterContent($profile, $enabledTypes, $featuredMediaId);

        $redirectUrl = add_query_arg(
            [
                'page' => 'pwt-content-forms',
                'pwt_seed_status' => 'success',
                'pwt_seed_terms' => (string) $summary['terms'],
                'pwt_seed_posts' => (string) $summary['posts'],
                'pwt_seed_media' => (string) $summary['featured_images'],
                'pwt_seed_profile' => $profile,
            ],
            admin_url('admin.php')
        );

        wp_safe_redirect($redirectUrl);
        exit;
    }

    /**
     * @param string[] $enabledTypes
     * @return array{terms:int,posts:int,featured_images:int}
     */
    private function importStarterContent(string $profile, array $enabledTypes, int $featuredMediaId): array
    {
        $createdTerms = 0;
        $createdPosts = 0;
        $featuredAssigned = 0;

        $taxonomyMap = [
            'pwt_safari_zone' => [
                'Madla Gate Core Zone',
                'Hinauta Gate Core Zone',
                'Akola Buffer Zone',
            ],
            'pwt_vehicle_type' => [
                'Open Gypsy (6 Seater)',
                'Canter (20 Seater)',
                'SUV Transfer',
            ],
            'pwt_season' => [
                'Peak (Nov-Feb)',
                'Shoulder (Mar-Jun)',
                'Monsoon (Jul-Oct)',
            ],
            'pwt_package_category' => [
                'Family Safari Packages',
                'Couple Getaway Packages',
                'Weekend Tours',
            ],
            'pwt_activity' => [
                'Tiger Safari',
                'Bird Watching',
                'Waterfall Trail',
            ],
            'pwt_destination_category' => [
                'Wildlife',
                'Nature Retreat',
                'Adventure',
            ],
        ];

        foreach ($taxonomyMap as $taxonomy => $terms) {
            if (!taxonomy_exists($taxonomy)) {
                continue;
            }

            foreach ($terms as $termName) {
                if (term_exists($termName, $taxonomy)) {
                    continue;
                }

                $created = wp_insert_term($termName, $taxonomy);
                if (!is_wp_error($created)) {
                    ++$createdTerms;
                }
            }
        }

        $posts = [
            [
                'post_type' => 'pwt_destination',
                'title' => 'Panna Tiger Reserve Core Experience',
                'excerpt' => 'A premium wildlife destination for tiger tracking, birding, and scenic forest drives.',
                'content' => 'Panna Tiger Reserve blends forest, river, and plateau landscapes for rich wildlife encounters. This destination is ideal for families, couples, and photography travelers who want guided, safe, and memorable jungle experiences.',
                'status' => 'publish',
                'meta' => [],
                'terms' => [
                    'pwt_destination_category' => ['Wildlife', 'Nature Retreat'],
                    'pwt_activity' => ['Tiger Safari', 'Bird Watching'],
                ],
            ],
            [
                'post_type' => 'pwt_safari',
                'title' => 'Sunrise Tiger Safari - Madla Gate',
                'excerpt' => 'Morning core-zone safari with naturalist support and priority wildlife tracking routes.',
                'content' => 'Start early and enter via Madla Gate for peak wildlife movement. This safari is curated for strong sightings, practical route flexibility, and smooth on-ground coordination.',
                'status' => 'publish',
                'meta' => [
                    'duration' => '4 to 5 hours',
                    'shift' => 'morning',
                    'meeting_point' => 'Madla Gate Reporting Point',
                    'regular_price' => '4500',
                    'offer_price' => '3999',
                ],
                'terms' => [
                    'pwt_safari_zone' => ['Madla Gate Core Zone'],
                    'pwt_vehicle_type' => ['Open Gypsy (6 Seater)'],
                    'pwt_season' => ['Peak (Nov-Feb)', 'Shoulder (Mar-Jun)'],
                ],
            ],
            [
                'post_type' => 'pwt_package',
                'title' => '2N/3D Panna Tiger Escape',
                'excerpt' => 'A quick and comfortable wildlife break with one sunrise safari, local transfers, and curated stay.',
                'content' => 'Designed for weekend travelers, this package combines safari timing, stay comfort, and local coordination. Suitable for families and first-time visitors looking for a complete short itinerary.',
                'status' => 'publish',
                'meta' => [
                    'regular_price' => '18500',
                    'offer_price' => '16999',
                    'duration' => '2 Nights / 3 Days',
                    'days' => '3',
                    'nights' => '2',
                    'peak_multiplier' => '1.20',
                    'shoulder_multiplier' => '1.00',
                    'monsoon_multiplier' => '0.88',
                ],
                'terms' => [
                    'pwt_package_category' => ['Family Safari Packages', 'Weekend Tours'],
                    'pwt_activity' => ['Tiger Safari', 'Bird Watching'],
                    'pwt_season' => ['Peak (Nov-Feb)', 'Shoulder (Mar-Jun)'],
                ],
            ],
            [
                'post_type' => 'pwt_faq',
                'title' => 'What is the best time to visit Panna Tiger Reserve?',
                'excerpt' => '',
                'content' => 'The best period for wildlife sightings is from November to June. Winter is pleasant, while late summer can improve waterhole sightings.',
                'status' => 'publish',
                'meta' => [],
                'terms' => [],
            ],
            [
                'post_type' => 'pwt_faq',
                'title' => 'How many safaris should I book for good sightings?',
                'excerpt' => '',
                'content' => 'For better sighting probability, book at least two drives: one sunrise and one evening safari.',
                'status' => 'publish',
                'meta' => [],
                'terms' => [],
            ],
            [
                'post_type' => 'pwt_testimonial',
                'title' => 'Smooth Family Safari Planning',
                'excerpt' => '',
                'content' => 'Our 3-day family tour was coordinated perfectly. Safari timing, stay, and transfers were all smooth and professional.',
                'status' => 'publish',
                'meta' => [],
                'terms' => [],
            ],
            [
                'post_type' => 'pwt_resort',
                'title' => 'Forest Edge Eco Resort - Panna',
                'excerpt' => 'Comfortable eco-stay with quick access to safari gates and curated local hospitality.',
                'content' => 'Forest Edge Eco Resort provides clean rooms, local cuisine, and timely safari support. Suitable for families and photographers who value location and practical safari logistics.',
                'status' => 'publish',
                'meta' => [],
                'terms' => [],
                'profiles' => ['full'],
            ],
            [
                'post_type' => 'pwt_vehicle',
                'title' => 'Open Gypsy 6-Seater - Safari Fleet',
                'excerpt' => 'Official safari-compatible open vehicle with trained driver support.',
                'content' => 'Primary vehicle for core and buffer wildlife drives. Best suited for small groups and photographers requiring open-angle visibility.',
                'status' => 'publish',
                'meta' => [],
                'terms' => [
                    'pwt_vehicle_type' => ['Open Gypsy (6 Seater)'],
                ],
                'profiles' => ['full'],
            ],
            [
                'post_type' => 'pwt_review',
                'title' => 'Memorable Tiger Sighting Experience',
                'excerpt' => 'Well-coordinated safari and friendly support team throughout the trip.',
                'content' => 'From pickup to safari execution, everything was on time. We were briefed clearly and had a memorable tiger sighting in the core zone.',
                'status' => 'publish',
                'meta' => [
                    'rating' => '5',
                    'guest_city' => 'Bhopal',
                    'verified' => '1',
                ],
                'terms' => [],
                'profiles' => ['full'],
            ],
        ];

        foreach ($posts as $postConfig) {
            $allowedProfiles = $postConfig['profiles'] ?? ['basic', 'full'];
            if (!in_array($profile, $allowedProfiles, true)) {
                continue;
            }

            if (!in_array($postConfig['post_type'], $enabledTypes, true)) {
                continue;
            }

            if ($this->postExists($postConfig['post_type'], $postConfig['title'])) {
                continue;
            }

            $postId = wp_insert_post(
                [
                    'post_type' => $postConfig['post_type'],
                    'post_title' => $postConfig['title'],
                    'post_excerpt' => $postConfig['excerpt'],
                    'post_content' => $postConfig['content'],
                    'post_status' => $postConfig['status'],
                ],
                true
            );

            if (is_wp_error($postId) || !is_numeric($postId)) {
                continue;
            }

            ++$createdPosts;

            foreach ($postConfig['meta'] as $metaKey => $metaValue) {
                update_post_meta((int) $postId, (string) $metaKey, (string) $metaValue);
            }

            if (
                $featuredMediaId > 0
                && wp_attachment_is_image($featuredMediaId)
                && !has_post_thumbnail((int) $postId)
            ) {
                if (set_post_thumbnail((int) $postId, $featuredMediaId)) {
                    ++$featuredAssigned;
                }
            }

            foreach ($postConfig['terms'] as $taxonomy => $termNames) {
                if (!taxonomy_exists((string) $taxonomy)) {
                    continue;
                }

                $termIds = [];
                foreach ($termNames as $termName) {
                    $term = term_exists((string) $termName, (string) $taxonomy);
                    if (is_array($term) && !empty($term['term_id'])) {
                        $termIds[] = (int) $term['term_id'];
                    }
                }

                if (!empty($termIds)) {
                    wp_set_object_terms((int) $postId, $termIds, (string) $taxonomy);
                }
            }
        }

        return [
            'terms' => $createdTerms,
            'posts' => $createdPosts,
            'featured_images' => $featuredAssigned,
        ];
    }

    private function latestAttachmentId(): int
    {
        $ids = get_posts([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'post_mime_type' => 'image',
            'posts_per_page' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);

        if (empty($ids)) {
            return 0;
        }

        return absint($ids[0]);
    }

    private function postExists(string $postType, string $title): bool
    {
        $query = new \WP_Query([
            'post_type' => $postType,
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'title' => $title,
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'suppress_filters' => true,
        ]);

        if ($query->have_posts()) {
            return true;
        }

        return false;
    }
}
