<?php
declare(strict_types=1);
namespace PWT\SCF\Groups;
defined('ABSPATH') || exit;
use PWT\SCF\FieldGroup;

final class SafariScheduleFields extends FieldGroup
{
    public function register(): void
    {
        $this->addGroup([
            'key' => 'group_pwt_safari_schedule_details',
            'title' => __('Safari Schedule Details', 'wildtours-plugin'),
            'location' => [[[
                'param' => 'post_type',
                'operator' => '=',
                'value' => 'pwt_safari_schedule',
            ]]],
            'fields' => [
                ['key'=>'field_pwt_schedule_safari','label'=>__('Safari','wildtours-plugin'),'name'=>'safari_id','type'=>'post_object','post_type'=>['pwt_safari'],'return_format'=>'id'],
                ['key'=>'field_pwt_schedule_date','label'=>__('Safari Date','wildtours-plugin'),'name'=>'service_date','type'=>'date_picker','return_format'=>'Y-m-d'],
                ['key'=>'field_pwt_schedule_shift','label'=>__('Shift','wildtours-plugin'),'name'=>'shift','type'=>'select','choices'=>['morning'=>'Morning','afternoon'=>'Afternoon','full_day'=>'Full Day']],
                ['key'=>'field_pwt_schedule_vehicle','label'=>__('Assigned Vehicle','wildtours-plugin'),'name'=>'vehicle_id','type'=>'post_object','post_type'=>['pwt_vehicle'],'return_format'=>'id'],
                ['key'=>'field_pwt_schedule_capacity','label'=>__('Passenger Capacity','wildtours-plugin'),'name'=>'capacity','type'=>'number','min'=>1],
                ['key'=>'field_pwt_schedule_gate','label'=>__('Entry Gate','wildtours-plugin'),'name'=>'entry_gate','type'=>'text'],
                ['key'=>'field_pwt_schedule_status','label'=>__('Status','wildtours-plugin'),'name'=>'schedule_status','type'=>'select','choices'=>['open'=>'Open','closed'=>'Closed','cancelled'=>'Cancelled']],
            ],
        ]);
    }
}
