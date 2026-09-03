<?php
declare(strict_types=1);
namespace PWT\Sales\Quotations;
defined('ABSPATH') || exit;
final class QuoteRepository {
 public const POST_TYPE='pwt_quote';
 public function register(): void { add_action('init', function(){ register_post_type(self::POST_TYPE,['label'=>'Quotations','public'=>false,'show_ui'=>true,'show_in_menu'=>'pwt-dashboard','supports'=>['title','editor'],'capability_type'=>'post','map_meta_cap'=>true]); }); }
 public function create(array $data): int { $id=wp_insert_post(['post_type'=>self::POST_TYPE,'post_status'=>'publish','post_title'=>sanitize_text_field($data['title']??'Quotation')]); foreach(['lead_id','customer_id','travel_dates','guests','subtotal','discount','taxes','total','advance','status'] as $k){if(isset($data[$k])) update_post_meta($id,'_pwt_quote_'.$k,sanitize_text_field((string)$data[$k]));} return (int)$id; }
}
