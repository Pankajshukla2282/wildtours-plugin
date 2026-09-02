<?php
declare(strict_types=1);
namespace PWT\SCF\Groups;
defined('ABSPATH') || exit;
use PWT\SCF\FieldGroup;

final class RoomTypeFields extends FieldGroup
{
    public function register(): void
    {
        $this->addGroup([
            'key' => 'group_pwt_room_type_details',
            'title' => __('Room Type Details', 'wildtours-plugin'),
            'location' => [[[
                'param' => 'post_type',
                'operator' => '=',
                'value' => 'pwt_room_type',
            ]]],
            'fields' => [
                ['key'=>'field_pwt_room_resort','label'=>__('Resort','wildtours-plugin'),'name'=>'resort_id','type'=>'post_object','post_type'=>['pwt_resort'],'return_format'=>'id'],
                ['key'=>'field_pwt_room_capacity','label'=>__('Max Occupancy','wildtours-plugin'),'name'=>'max_occupancy','type'=>'number','min'=>1],
                ['key'=>'field_pwt_room_adults','label'=>__('Max Adults','wildtours-plugin'),'name'=>'max_adults','type'=>'number','min'=>1],
                ['key'=>'field_pwt_room_children','label'=>__('Max Children','wildtours-plugin'),'name'=>'max_children','type'=>'number','min'=>0],
                ['key'=>'field_pwt_room_base_rate','label'=>__('Base Nightly Rate (INR)','wildtours-plugin'),'name'=>'base_rate','type'=>'number','min'=>0,'step'=>'0.01'],
                ['key'=>'field_pwt_room_meal_plan','label'=>__('Meal Plans','wildtours-plugin'),'name'=>'meal_plans','type'=>'checkbox','choices'=>['room_only'=>'Room Only','breakfast'=>'Breakfast','half_board'=>'Half Board','full_board'=>'Full Board']],
                ['key'=>'field_pwt_room_amenities','label'=>__('Amenities','wildtours-plugin'),'name'=>'amenities','type'=>'checkbox','choices'=>['ac'=>'AC','wifi'=>'Wi-Fi','tv'=>'TV','hot_water'=>'Hot Water','balcony'=>'Balcony','parking'=>'Parking']],
            ],
        ]);
    }
}
