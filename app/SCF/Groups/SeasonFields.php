<?php

declare(strict_types=1);

namespace PWT\SCF\Groups;

use PWT\SCF\FieldGroup;

defined('ABSPATH') || exit;

/**
 * Season term field group.
 *
 * Stores the recurring month range for each pwt_season term so the pricing
 * layer can resolve season-specific rates.
 */
final class SeasonFields extends FieldGroup
{
    /**
     * Register field group.
     */
    public function register(): void
    {
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[(string) $i] = $this->monthLabel($i);
        }

        $this->addGroup([
            'key' => 'group_pwt_season_dates',

            'title' => __('Season Dates', 'wildtours-plugin'),

            'location' => [[[
                'param' => 'taxonomy',
                'operator' => '=',
                'value' => 'pwt_season',
            ]]],

            'fields' => [

                [
                    'key' => 'field_pwt_season_start',
                    'label' => __('Start Month', 'wildtours-plugin'),
                    'name' => 'season_start',
                    'type' => 'select',
                    'choices' => $months,
                    'instructions' => __('First month of the season.', 'wildtours-plugin'),
                ],

                [
                    'key' => 'field_pwt_season_end',
                    'label' => __('End Month', 'wildtours-plugin'),
                    'name' => 'season_end',
                    'type' => 'select',
                    'choices' => $months,
                    'instructions' => __('Last month of the season. Ranges can wrap across the new year (for example November to February).', 'wildtours-plugin'),
                ],
            ],
        ]);
    }

    private function monthLabel(int $month): string
    {
        $names = [
            1  => __('January', 'wildtours-plugin'),
            2  => __('February', 'wildtours-plugin'),
            3  => __('March', 'wildtours-plugin'),
            4  => __('April', 'wildtours-plugin'),
            5  => __('May', 'wildtours-plugin'),
            6  => __('June', 'wildtours-plugin'),
            7  => __('July', 'wildtours-plugin'),
            8  => __('August', 'wildtours-plugin'),
            9  => __('September', 'wildtours-plugin'),
            10 => __('October', 'wildtours-plugin'),
            11 => __('November', 'wildtours-plugin'),
            12 => __('December', 'wildtours-plugin'),
        ];

        return $names[$month] ?? (string) $month;
    }
}