<?php
declare(strict_types=1);
namespace PWT\Sales\Leads;
defined('ABSPATH') || exit;
final class LeadRepository {
 public const POST_TYPE='pwt_lead';
 public function register(): void { add_action('init', function(){ register_post_type(self::POST_TYPE,['label'=>'Leads','public'=>false,'show_ui'=>true,'show_in_menu'=>'pwt-dashboard','supports'=>['title','editor'],'capability_type'=>'post','map_meta_cap'=>true]); }); }
 public function create(array $data): int { $id=wp_insert_post(['post_type'=>self::POST_TYPE,'post_status'=>'publish','post_title'=>sanitize_text_field($data['name']??'New Lead'),'post_content'=>sanitize_textarea_field($data['notes']??'')]); foreach(['phone','email','source','destination','travel_dates','guests','budget','assigned_to','status','priority','follow_up_date','lost_reason'] as $k){ if(isset($data[$k])) update_post_meta($id,'_pwt_lead_'.$k,is_scalar($data[$k])?sanitize_text_field((string)$data[$k]):''); } return (int)$id; }
}
