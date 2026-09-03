<?php
declare(strict_types=1);
namespace PWT\Staff\CRM;
defined('ABSPATH') || exit;
final class FeedbackRepository {
 public const POST_TYPE='pwt_customer_feedback';
 public function register(): void { register_post_type(self::POST_TYPE,['label'=>'Customer Feedback','public'=>false,'show_ui'=>false,'supports'=>['title']]); }
 public function create(array $d): int { $id=wp_insert_post(['post_type'=>self::POST_TYPE,'post_status'=>'publish','post_title'=>sanitize_text_field($d['title']??'Tour Feedback')]); if(is_wp_error($id)) return 0; foreach(['booking_id','customer_id','overall_rating','safari_rating','accommodation_rating','transport_rating','service_rating','feedback','review_requested_at','created_at'] as $k) update_post_meta($id,'_pwt_feedback_'.$k,$d[$k]??($k==='created_at'?current_time('mysql'):'')); return (int)$id; }
 public function forCustomer(int $customer): array { return get_posts(['post_type'=>self::POST_TYPE,'post_status'=>'publish','numberposts'=>-1,'meta_key'=>'_pwt_feedback_customer_id','meta_value'=>$customer]); }
}
