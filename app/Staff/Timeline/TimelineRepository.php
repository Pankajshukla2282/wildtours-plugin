<?php
declare(strict_types=1);
namespace PWT\Staff\Timeline;
defined('ABSPATH') || exit;
final class TimelineRepository {
 public const POST_TYPE='pwt_ops_event';
 public function register(): void { register_post_type(self::POST_TYPE,['label'=>'Operations Timeline','public'=>false,'show_ui'=>false,'supports'=>['title','editor']]); }
 public function add(int $booking,string $title,string $message='',string $type='system',int $actor=0): int { $id=wp_insert_post(['post_type'=>self::POST_TYPE,'post_status'=>'publish','post_title'=>sanitize_text_field($title),'post_content'=>sanitize_textarea_field($message)]); if(is_wp_error($id)) return 0; update_post_meta($id,'_pwt_event_booking',$booking); update_post_meta($id,'_pwt_event_type',sanitize_key($type)); update_post_meta($id,'_pwt_event_actor',$actor); update_post_meta($id,'_pwt_event_at',current_time('mysql')); return (int)$id; }
 public function events(int $booking): array { return get_posts(['post_type'=>self::POST_TYPE,'post_status'=>'publish','numberposts'=>100,'orderby'=>'date','order'=>'DESC','meta_key'=>'_pwt_event_booking','meta_value'=>$booking]); }
}
