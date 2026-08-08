<?php
declare(strict_types=1);
namespace PWT\SCF\Groups;
defined('ABSPATH') || exit;
use PWT\SCF\FieldGroup;
final class RestaurantFields extends FieldGroup
{
    protected string $key = 'pwt_restaurant_fields';
    protected string $title = 'Restaurant Details';
    protected array $location = [['param'=>'post_type','operator'=>'==','value'=>'pwt_restaurant']];
    protected function fields(): array
    {
        return [
            ['key'=>'field_pwt_restaurant_price_range','label'=>'Price Range','name'=>'price_range','type'=>'select','choices'=>['budget'=>'Budget','mid'=>'Mid-range','premium'=>'Premium']],
            ['key'=>'field_pwt_restaurant_meal_types','label'=>'Meal Types','name'=>'meal_types','type'=>'checkbox','choices'=>['breakfast'=>'Breakfast','lunch'=>'Lunch','dinner'=>'Dinner','snacks'=>'Snacks']],
            ['key'=>'field_pwt_restaurant_capacity','label'=>'Seating Capacity','name'=>'seating_capacity','type'=>'number','min'=>0],
            ['key'=>'field_pwt_restaurant_phone','label'=>'Phone','name'=>'phone','type'=>'text'],
            ['key'=>'field_pwt_restaurant_reservation_url','label'=>'Reservation URL','name'=>'reservation_url','type'=>'url'],
            ['key'=>'field_pwt_restaurant_address','label'=>'Address','name'=>'address','type'=>'textarea'],
            ['key'=>'field_pwt_restaurant_opening','label'=>'Opening Time','name'=>'opening_time','type'=>'time'],
            ['key'=>'field_pwt_restaurant_closing','label'=>'Closing Time','name'=>'closing_time','type'=>'time'],
        ];
    }
}
