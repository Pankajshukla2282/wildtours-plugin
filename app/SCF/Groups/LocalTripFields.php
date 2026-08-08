<?php
declare(strict_types=1);
namespace PWT\SCF\Groups;
defined('ABSPATH') || exit;
use PWT\SCF\FieldGroup;
/** * Local trip field group. */ 
final class LocalTripFields extends FieldGroup
{
    /** * Register local trip fields. */ 
    public function register(): void
    {
        $this->addGroup([
            "key" => "group_pwt_local_trip_details",
            "title" => __("Local Trip Details", "wildtours-plugin"),
            "location" => [
                [
                    [
                        "param" => "post_type",
                        "operator" => "=",
                        "value" => "pwt_local_trip",
                    ],
                ],
            ],
            "fields" => [
                [
                    "key" => "field_pwt_trip_code",
                    "label" => __("Trip Code", "wildtours-plugin"),
                    "name" => "trip_code",
                    "type" => "text",
                ],
                [
                    "key" => "field_pwt_trip_duration",
                    "label" => __("Duration", "wildtours-plugin"),
                    "name" => "duration",
                    "type" => "text",
                ],
                [
                    "key" => "field_pwt_trip_base_price",
                    "label" => __("Base Price", "wildtours-plugin"),
                    "name" => "base_price",
                    "type" => "number",
                    "min" => 0,
                    "step" => "0.01",
                ],
                [
                    "key" => "field_pwt_trip_offer_price",
                    "label" => __("Offer Price", "wildtours-plugin"),
                    "name" => "offer_price",
                    "type" => "number",
                    "min" => 0,
                    "step" => "0.01",
                ],
                [
                    "key" => "field_pwt_trip_max_group",
                    "label" => __("Maximum Group Size", "wildtours-plugin"),
                    "name" => "max_group_size",
                    "type" => "number",
                    "min" => 1,
                ],
                [
                    "key" => "field_pwt_trip_difficulty",
                    "label" => __("Difficulty", "wildtours-plugin"),
                    "name" => "difficulty",
                    "type" => "select",
                    "choices" => [
                        "easy" => __("Easy", "wildtours-plugin"),
                        "moderate" => __("Moderate", "wildtours-plugin"),
                        "hard" => __("Hard", "wildtours-plugin"),
                    ],
                ],
                [
                    "key" => "field_pwt_trip_pickup",
                    "label" => __("Pickup / Meeting Point", "wildtours-plugin"),
                    "name" => "pickup_point",
                    "type" => "text",
                ],
                [
                    "key" => "field_pwt_trip_inclusions",
                    "label" => __("Inclusions", "wildtours-plugin"),
                    "name" => "inclusions",
                    "type" => "wysiwyg",
                ],
                [
                    "key" => "field_pwt_trip_exclusions",
                    "label" => __("Exclusions", "wildtours-plugin"),
                    "name" => "exclusions",
                    "type" => "wysiwyg",
                ],
            ],
        ]);
    }
}
