<?php
declare(strict_types=1);
namespace PWT\Staff\Vendors;
defined('ABSPATH') || exit;
final class VendorPerformanceService {
 public function rate(int $vendorId,int $rating,string $notes=''): void { update_post_meta($vendorId,'_pwt_vendor_performance_rating',max(1,min(5,$rating))); update_post_meta($vendorId,'_pwt_vendor_performance_notes',sanitize_textarea_field($notes)); update_post_meta($vendorId,'_pwt_vendor_performance_updated_at',current_time('mysql')); }
}
