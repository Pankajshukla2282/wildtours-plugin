<?php
declare(strict_types=1);
namespace PWT\SCF\Groups;
defined('ABSPATH') || exit;
use PWT\SCF\FieldGroup;
/** * Restaurant field group. */ 
final class RestaurantFields extends FieldGroup
{
    /** * Register restaurant fields. */ 
    public function register(): void
    {
        $this->addGroup([
            "key" => "group_pwt_restaurant_details",
            "title" => __("Restaurant Details", "wildtours-plugin"),
            "location" => [
                [
                    [
                        "param" => "post_type",
                        "operator" => "=",
                        "value" => "pwt_restaurant",
                    ],
                ],
            ],
            "fields" => [
                [
                    "key" => "field_pwt_restaurant_price_range",
                    "label" => __("Price Range", "wildtours-plugin"),
                    "name" => "price_range",
                    "type" => "select",
                    "choices" => [
                        "budget" => __("Budget", "wildtours-plugin"),
                        "mid" => __("Mid-range", "wildtours-plugin"),
                        "premium" => __("Premium", "wildtours-plugin"),
                    ],
                ],
                [
                    "key" => "field_pwt_restaurant_meal_types",
                    "label" => __("Meal Types", "wildtours-plugin"),
                    "name" => "meal_types",
                    "type" => "checkbox",
                    "choices" => [
                        "breakfast" => __("Breakfast", "wildtours-plugin"),
                        "lunch" => __("Lunch", "wildtours-plugin"),
                        "dinner" => __("Dinner", "wildtours-plugin"),
                        "snacks" => __("Snacks", "wildtours-plugin"),
                    ],
                ],
                [
                    "key" => "field_pwt_restaurant_capacity",
                    "label" => __("Seating Capacity", "wildtours-plugin"),
                    "name" => "seating_capacity",
                    "type" => "number",
                    "min" => 0,
                ],
                [
                    "key" => "field_pwt_restaurant_phone",
                    "label" => __("Phone", "wildtours-plugin"),
                    "name" => "phone",
                    "type" => "text",
                ],
                [
                    "key" => "field_pwt_restaurant_reservation_url",
                    "label" => __("Reservation URL", "wildtours-plugin"),
                    "name" => "reservation_url",
                    "type" => "url",
                ],
                [
                    "key" => "field_pwt_restaurant_address",
                    "label" => __("Address", "wildtours-plugin"),
                    "name" => "address",
                    "type" => "textarea",
                ],
                [
                    "key" => "field_pwt_restaurant_opening",
                    "label" => __("Opening Time", "wildtours-plugin"),
                    "name" => "opening_time",
                    "type" => "time",
                ],
                [
                    "key" => "field_pwt_restaurant_closing",
                    "label" => __("Closing Time", "wildtours-plugin"),
                    "name" => "closing_time",
                    "type" => "time",
                ],
            ],
        ]);
    }
}
