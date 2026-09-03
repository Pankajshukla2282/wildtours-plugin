<?php
declare(strict_types=1);
namespace PWT\Staff\Trips;
defined('ABSPATH') || exit;
final class TripRepository {
 public const POST_TYPE='pwt_trip_operation';
 public function register(): void { register_post_type(self::POST_TYPE,['label'=>'Trip Operations','public'=>false,'show_ui'=>false,'supports'=>['title']]); }
 public function create(array $d): int { $id=wp_insert_post(['post_type'=>self::POST_TYPE,'post_status'=>'publish','post_title'=>sanitize_text_field($d['title']??'Trip Operation')]); if(is_wp_error($id)) return 0; foreach(['booking_id','departure_date','status','guest_manifest','safari_status','hotel_status','pickup_status','emergency_notes','incident_report','completed_at'] as $k) update_post_meta($id,'_pwt_trip_'.$k,$d[$k]??''); return (int)$id; }
 public function update(int $id,array $d): void { foreach(['departure_date','status','guest_manifest','safari_status','hotel_status','pickup_status','emergency_notes','incident_report'] as $k) if(array_key_exists($k,$d)) update_post_meta($id,'_pwt_trip_'.$k,$d[$k]); if(($d['status']??'')==='completed') update_post_meta($id,'_pwt_trip_completed_at',current_time('mysql')); }
 public function trips(array $args=[]): array { $q=['post_type'=>self::POST_TYPE,'post_status'=>'publish','numberposts'=>200,'orderby'=>'meta_value','meta_key'=>'_pwt_trip_departure_date','order'=>'ASC']; if(!empty($args['date'])) $q['meta_query']=[['key'=>'_pwt_trip_departure_date','value'=>$args['date']]]; return get_posts($q); }
}
