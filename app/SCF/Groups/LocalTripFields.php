<?php
declare(strict_types=1);
namespace PWT\SCF\Groups;
defined('ABSPATH') || exit;
use PWT\SCF\FieldGroup;
final class LocalTripFields extends FieldGroup
{
    protected string $key = 'pwt_local_trip_fields';
    protected string $title = 'Local Trip Details';
    protected array $location = [['param'=>'post_type','operator'=>'==','value'=>'pwt_local_trip']];
    protected function fields(): array
    {
        return [
            ['key'=>'field_pwt_trip_code','label'=>'Trip Code','name'=>'trip_code','type'=>'text'],
            ['key'=>'field_pwt_trip_duration','label'=>'Duration','name'=>'duration','type'=>'text'],
            ['key'=>'field_pwt_trip_base_price','label'=>'Base Price','name'=>'base_price','type'=>'number','min'=>0,'step'=>'0.01'],
            ['key'=>'field_pwt_trip_offer_price','label'=>'Offer Price','name'=>'offer_price','type'=>'number','min'=>0,'step'=>'0.01'],
            ['key'=>'field_pwt_trip_max_group','label'=>'Maximum Group Size','name'=>'max_group_size','type'=>'number','min'=>1],
            ['key'=>'field_pwt_trip_difficulty','label'=>'Difficulty','name'=>'difficulty','type'=>'select','choices'=>['easy'=>'Easy','moderate'=>'Moderate','hard'=>'Hard']],
            ['key'=>'field_pwt_trip_pickup','label'=>'Pickup / Meeting Point','name'=>'pickup_point','type'=>'text'],
            ['key'=>'field_pwt_trip_inclusions','label'=>'Inclusions','name'=>'inclusions','type'=>'wysiwyg'],
            ['key'=>'field_pwt_trip_exclusions','label'=>'Exclusions','name'=>'exclusions','type'=>'wysiwyg'],
        ];
    }
}
