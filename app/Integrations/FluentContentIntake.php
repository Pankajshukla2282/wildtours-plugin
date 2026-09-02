<?php

namespace PWT\Integrations;

defined('ABSPATH') || exit;

class FluentContentIntake
{
    public function register(): void
    {
        add_action('fluentform_submission_inserted', [$this, 'handleSubmission'], 20, 3);
    }

    public function handleSubmission($entryId, $formData, $form): void
    {
        $entryId = absint((string) $entryId);
        $formData = $this->normalizeFormData($formData);

        if ($entryId <= 0 || empty($formData)) {
            return;
        }

        $formTitle = $this->formTitle($form);

        $map = $this->mapForForm($formTitle);
        if (empty($map)) {
            return;
        }

        $title = $this->resolveTitle($formData, $map['post_type']);

        $slug = sanitize_title((string) ($formData['slug'] ?? ''));
        $excerpt = sanitize_textarea_field((string) ($formData['excerpt'] ?? ($formData['textarea'] ?? '')));
        $content = $this->resolveContent($formData);

        $postId = wp_insert_post([
            'post_type' => $map['post_type'],
            'post_status' => 'draft',
            'post_title' => $title,
            'post_name' => $slug,
            'post_excerpt' => $excerpt,
            'post_content' => $content,
        ], true);

        if (is_wp_error($postId)) {
            return;
        }

        update_post_meta($postId, '_pwt_source', 'fluentform_intake');
        update_post_meta($postId, '_pwt_source_form', $formTitle);
        update_post_meta($postId, '_pwt_source_entry_id', $entryId);

        foreach ($map['meta_fields'] as $field) {
            $value = sanitize_text_field((string) $this->resolveField($formData, $field));
            if ($value !== '') {
                update_post_meta($postId, $field, $value);
            }
        }

        if ($map['post_type'] === 'pwt_review' && isset($formData['verified'])) {
            update_post_meta($postId, 'verified', sanitize_key((string) $formData['verified']) === '1' ? '1' : '0');
        }

        if (isset($formData['featured_media'])) {
            $featuredMedia = absint((string) $formData['featured_media']);
            if ($featuredMedia > 0) {
                set_post_thumbnail($postId, $featuredMedia);
            }
        }

        foreach ($map['taxonomies'] as $field => $taxonomy) {
            if (!taxonomy_exists($taxonomy)) {
                continue;
            }

            $idsRaw = sanitize_text_field((string) $this->resolveField($formData, $field));
            $ids = array_values(array_filter(array_map('absint', array_map('trim', explode(',', $idsRaw)))));
            if (!empty($ids)) {
                wp_set_object_terms($postId, $ids, $taxonomy);
            }
        }
    }

    private function normalizeFormData($formData): array
    {
        if (is_array($formData)) {
            return $formData;
        }

        if (is_object($formData)) {
            $decoded = json_decode(wp_json_encode($formData), true);
            return is_array($decoded) ? $decoded : [];
        }

        if (is_string($formData)) {
            $decoded = json_decode($formData, true);
            if (is_array($decoded)) {
                return $decoded;
            }

            parse_str($formData, $parsed);
            return is_array($parsed) ? $parsed : [];
        }

        return [];
    }

    private function resolveTitle(array $formData, string $postType): string
    {
        $candidates = [
            $formData['title'] ?? '',
            $formData['input_text'] ?? '',
            $formData['subject'] ?? '',
        ];

        if (isset($formData['names']) && is_array($formData['names'])) {
            $name = trim(((string) ($formData['names']['first_name'] ?? '')) . ' ' . ((string) ($formData['names']['last_name'] ?? '')));
            $candidates[] = $name;
        }

        foreach ($candidates as $candidate) {
            $value = sanitize_text_field((string) $candidate);
            if ($value !== '') {
                return $value;
            }
        }

        return sprintf(
            /* translators: %s: post type */
            __('New %s Intake', 'wildtours-plugin'),
            $postType
        ) . ' ' . gmdate('Y-m-d H:i');
    }

    private function resolveContent(array $formData): string
    {
        $primary = (string) ($formData['content'] ?? ($formData['message'] ?? ($formData['textarea'] ?? '')));
        $primary = wp_kses_post($primary);

        if ($primary !== '') {
            return $primary;
        }

        $lines = [];
        foreach ($formData as $key => $value) {
            if (is_array($value)) {
                $value = wp_json_encode($value);
            }

            $text = sanitize_text_field((string) $value);
            if ($text !== '') {
                $lines[] = $key . ': ' . $text;
            }
        }

        return wp_kses_post(implode("\n", $lines));
    }

    private function resolveField(array $formData, string $field): string
    {
        if (isset($formData[$field])) {
            return (string) $formData[$field];
        }

        $aliases = [
            'regular_price' => ['price', 'package_price'],
            'offer_price' => ['discount_price', 'sale_price'],
            'duration' => ['trip_duration'],
            'featured_media' => ['media_id', 'image_id'],
            'rating' => ['review_rating'],
            'guest_city' => ['city'],
            'pwt_season' => ['season_ids'],
            'pwt_activity' => ['activity_ids'],
            'pwt_safari_zone' => ['safari_zone_ids'],
            'pwt_vehicle_type' => ['vehicle_type_ids'],
            'pwt_package_category' => ['package_category_ids'],
            'pwt_destination_category' => ['destination_category_ids'],
        ];

        foreach (($aliases[$field] ?? []) as $alias) {
            if (isset($formData[$alias])) {
                return (string) $formData[$alias];
            }
        }

        return '';
    }

    private function formTitle($form): string
    {
        if (is_array($form)) {
            return (string) ($form['title'] ?? '');
        }

        if (is_object($form) && isset($form->title)) {
            return (string) $form->title;
        }

        return '';
    }

    private function mapForForm(string $formTitle): array
    {
        $maps = [
            'PWT Content Intake - Package' => [
                'post_type' => 'pwt_package',
                'meta_fields' => ['regular_price', 'offer_price', 'duration', 'peak_multiplier', 'shoulder_multiplier', 'monsoon_multiplier'],
                'taxonomies' => [
                    'pwt_season' => 'pwt_season',
                    'pwt_activity' => 'pwt_activity',
                    'pwt_package_category' => 'pwt_package_category',
                ],
            ],
            'PWT Content Intake - Safari' => [
                'post_type' => 'pwt_safari',
                'meta_fields' => [],
                'taxonomies' => [
                    'pwt_season' => 'pwt_season',
                    'pwt_vehicle_type' => 'pwt_vehicle_type',
                    'pwt_safari_zone' => 'pwt_safari_zone',
                ],
            ],
            'PWT Content Intake - Destination' => [
                'post_type' => 'pwt_destination',
                'meta_fields' => [],
                'taxonomies' => [
                    'pwt_activity' => 'pwt_activity',
                    'pwt_destination_category' => 'pwt_destination_category',
                ],
            ],
            'PWT Content Intake - Resort and Homestay' => [
                'post_type' => 'pwt_resort',
                'meta_fields' => [],
                'taxonomies' => [],
            ],
            'PWT Content Intake - Vehicle' => [
                'post_type' => 'pwt_vehicle',
                'meta_fields' => [],
                'taxonomies' => [
                    'pwt_vehicle_type' => 'pwt_vehicle_type',
                ],
            ],
            'PWT Content Intake - FAQ/Testimonial/Review' => [
                'post_type' => $this->resolveSocialType(),
                'meta_fields' => ['rating', 'guest_city'],
                'taxonomies' => [],
            ],
        ];

        return $maps[$formTitle] ?? [];
    }

    private function resolveSocialType(): string
    {
        $type = isset($_POST['pwt_content_type']) ? sanitize_key((string) $_POST['pwt_content_type']) : '';

        if (in_array($type, ['pwt_faq', 'pwt_testimonial', 'pwt_review'], true)) {
            return $type;
        }

        return 'pwt_faq';
    }
}
