<?php
declare(strict_types=1);
namespace PWT\SCF\Groups;
defined('ABSPATH') || exit;
use PWT\SCF\FieldGroup;

final class RoomUnitFields extends FieldGroup
{
    public function register(): void
    {
        $this->addGroup([
            'key' => 'group_pwt_room_unit_details',
            'title' => __('Room Unit Details', 'wildtours-plugin'),
            'location' => [[[
                'param' => 'post_type',
                'operator' => '=',
                'value' => 'pwt_room_unit',
            ]]],
            'fields' => [
                ['key'=>'field_pwt_unit_room_type','label'=>__('Room Type','wildtours-plugin'),'name'=>'room_type_id','type'=>'post_object','post_type'=>['pwt_room_type'],'return_format'=>'id'],
                ['key'=>'field_pwt_unit_number','label'=>__('Room Number','wildtours-plugin'),'name'=>'room_number','type'=>'text'],
                ['key'=>'field_pwt_unit_floor','label'=>__('Floor','wildtours-plugin'),'name'=>'floor','type'=>'number'],
                ['key'=>'field_pwt_unit_status','label'=>__('Operational Status','wildtours-plugin'),'name'=>'operational_status','type'=>'select','choices'=>['active'=>'Active','maintenance'=>'Maintenance','out_of_service'=>'Out of Service']],
            ],
        ]);
    }
}
