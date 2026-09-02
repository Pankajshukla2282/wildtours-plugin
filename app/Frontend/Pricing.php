<?php

namespace PWT\Frontend;

defined('ABSPATH') || exit;

class Pricing
{
    public static function detectSeason(string $travelDate): array
    {
        $timestamp = strtotime($travelDate);

        if (!$timestamp) {
            return [
                'slug' => 'shoulder',
                'label' => __('Shoulder Season', 'wildtours-plugin'),
                'multiplier' => 1.0,
            ];
        }

        $month = (int) date('n', $timestamp);

        if (in_array($month, [11, 12, 1, 2], true)) {
            return [
                'slug' => 'peak',
                'label' => __('Peak Season', 'wildtours-plugin'),
                'multiplier' => 1.2,
            ];
        }

        if (in_array($month, [7, 8, 9, 10], true)) {
            return [
                'slug' => 'monsoon',
                'label' => __('Monsoon Season', 'wildtours-plugin'),
                'multiplier' => 0.85,
            ];
        }

        return [
            'slug' => 'shoulder',
            'label' => __('Shoulder Season', 'wildtours-plugin'),
            'multiplier' => 1.0,
        ];
    }

    public static function packageBasePrice(int $packageId): float
    {
        $offer = (float) get_post_meta($packageId, 'offer_price', true);
        $regular = (float) get_post_meta($packageId, 'regular_price', true);

        if ($offer > 0) {
            return $offer;
        }

        return $regular > 0 ? $regular : 0.0;
    }

    public static function packageSeasonMultiplier(int $packageId, string $seasonSlug, float $defaultMultiplier): float
    {
        $metaMap = [
            'peak' => 'peak_multiplier',
            'shoulder' => 'shoulder_multiplier',
            'monsoon' => 'monsoon_multiplier',
        ];

        if (!isset($metaMap[$seasonSlug])) {
            return $defaultMultiplier;
        }

        $meta = (float) get_post_meta($packageId, $metaMap[$seasonSlug], true);

        if ($meta <= 0) {
            return $defaultMultiplier;
        }

        return $meta;
    }

    public static function calculateEstimate(int $packageId, int $persons, string $travelDate): array
    {
        $persons = max(1, $persons);

        $season = self::detectSeason($travelDate);
        $basePrice = self::packageBasePrice($packageId);

        if ($basePrice <= 0) {
            return [
                'base_price' => 0,
                'season_label' => $season['label'],
                'season_multiplier' => $season['multiplier'],
                'persons' => $persons,
                'estimated_total' => 0,
                'formatted_total' => __('Price on request', 'wildtours-plugin'),
            ];
        }

        $multiplier = self::packageSeasonMultiplier($packageId, $season['slug'], (float) $season['multiplier']);
        $estimatedTotal = $basePrice * $persons * $multiplier;

        return [
            'base_price' => round($basePrice, 2),
            'season_label' => $season['label'],
            'season_multiplier' => round($multiplier, 2),
            'persons' => $persons,
            'estimated_total' => round($estimatedTotal, 2),
            'formatted_total' => sprintf(__('INR %s', 'wildtours-plugin'), number_format_i18n((float) $estimatedTotal, 0)),
        ];
    }
}
