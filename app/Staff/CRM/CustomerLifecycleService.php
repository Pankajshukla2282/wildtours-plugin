<?php
declare(strict_types=1);
namespace PWT\Staff\CRM;
defined('ABSPATH') || exit;
final class CustomerLifecycleService {
 public function bookingCount(int $customerId): int { return count(get_posts(['post_type'=>'pwt_booking','post_status'=>'any','numberposts'=>-1,'meta_key'=>'_pwt_booking_customer_id','meta_value'=>$customerId,'fields'=>'ids'])); }
 public function status(int $customerId): string { return $this->bookingCount($customerId)>1?'Repeat Customer':'Customer'; }
}
