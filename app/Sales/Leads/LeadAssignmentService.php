<?php
declare(strict_types=1);
namespace PWT\Sales\Leads;
defined('ABSPATH') || exit;
final class LeadAssignmentService {
 public function assign(int $leadId,int $userId): bool { if(get_post_type($leadId)!==LeadRepository::POST_TYPE||$userId<1)return false; update_post_meta($leadId,'_pwt_lead_assigned_to',$userId); update_post_meta($leadId,'_pwt_lead_assigned_at',current_time('mysql')); do_action('pwt_lead_assigned',$leadId,$userId); return true; }
 public function overdue(int $leadId): bool { $date=(string)get_post_meta($leadId,'_pwt_lead_follow_up_date',true); $status=(string)get_post_meta($leadId,'_pwt_lead_status',true); return $date!=='' && strtotime($date)<strtotime(current_time('Y-m-d')) && !in_array($status,['won','lost','not_interested'],true); }
}
