<?php
declare(strict_types=1); namespace PWT\Staff\CRM; defined('ABSPATH')||exit;
final class CustomerSegmentationService { public function segment(int $customer):string{$count=count(get_posts(['post_type'=>'pwt_booking','numberposts'=>-1,'meta_key'=>'_pwt_customer_id','meta_value'=>$customer])); if($count>=5)return 'VIP Customer'; if($count>=2)return 'Repeat Customer'; return 'First-Time Customer';} }
