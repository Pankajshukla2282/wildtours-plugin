<?php
declare(strict_types=1);

namespace PWT\Packages;

defined('ABSPATH') || exit;

/**
 * Normalizes bookable WordPress resources for package composition.
 * Keeps package components limited to published, compatible resources.
 */
final class ResourceCatalogService
{
    private const TYPES = [
        'resort' => 'pwt_resort',
        'vehicle' => 'pwt_vehicle',
        'safari' => 'pwt_safari',
        'safari_schedule' => 'pwt_safari_schedule',
    ];

    public function postType(string $resourceType): ?string
    {
        $resourceType = sanitize_key($resourceType);
        return self::TYPES[$resourceType] ?? null;
    }

    public function isValid(string $resourceType, int $resourceId): bool
    {
        $postType = $this->postType($resourceType);
        if (!$postType || $resourceId <= 0) {
            return false;
        }

        $post = get_post($resourceId);
        return $post instanceof \WP_Post
            && $post->post_type === $postType
            && $post->post_status === 'publish';
    }

    public function details(string $resourceType, int $resourceId): ?array
    {
        if (!$this->isValid($resourceType, $resourceId)) {
            return null;
        }

        $resourceType = sanitize_key($resourceType);
        $priceKeys = match ($resourceType) {
            'resort' => ['price_per_night', 'offer_price'],
            'vehicle' => ['price_per_day', 'offer_price'],
            'safari' => ['offer_price', 'regular_price'],
            'safari_schedule' => ['offer_price', 'regular_price'],
            default => [],
        };

        $price = 0.0;
        foreach ($priceKeys as $key) {
            $value = (float) get_post_meta($resourceId, $key, true);
            if ($value > 0) {
                $price = $value;
                break;
            }
        }

        $capacity = (int) get_post_meta($resourceId, 'capacity', true);
        if ($capacity <= 0) {
            $capacity = (int) get_post_meta($resourceId, 'passenger_capacity', true);
        }

        return [
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'name' => get_the_title($resourceId),
            'vendor_id' => absint(get_post_meta($resourceId, 'vendor_id', true)),
            'base_price' => max(0, $price),
            'capacity' => max(0, $capacity),
        ];
    }

    /** @return array<int,array<string,mixed>> */
    public function resources(string $resourceType): array
    {
        $postType = $this->postType($resourceType);
        if (!$postType) {
            return [];
        }

        $ids = get_posts([
            'post_type' => $postType,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'fields' => 'ids',
        ]);

        $items = [];
        foreach ($ids as $id) {
            $details = $this->details($resourceType, (int) $id);
            if ($details !== null) {
                $items[] = $details;
            }
        }
        return $items;
    }
}
