<?php
declare(strict_types=1);
namespace PWT\Staff\CRM;
defined('ABSPATH') || exit;
final class ComplaintRepository {
 public const POST_TYPE='pwt_complaint';
 public function register(): void { register_post_type(self::POST_TYPE,['label'=>'Complaints','public'=>false,'show_ui'=>false,'supports'=>['title']]); }
 public function create(array $d): int { $id=wp_insert_post(['post_type'=>self::POST_TYPE,'post_status'=>'publish','post_title'=>sanitize_text_field($d['subject']??'Customer Complaint')]); if(is_wp_error($id)) return 0; foreach(['booking_id','customer_id','severity','assigned_user','description','status','resolution_notes','resolved_at','created_at'] as $k) update_post_meta($id,'_pwt_complaint_'.$k,$d[$k]??($k==='status'?'open':($k==='created_at'?current_time('mysql'):''))); return (int)$id; }
}
