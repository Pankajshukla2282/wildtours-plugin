<?php
declare(strict_types=1);
namespace PWT\Sales\Dashboard;
defined('ABSPATH') || exit;
use PWT\Sales\Leads\LeadRepository;
final class SalesDashboardService { public function summary(?int $userId=null): array { $ids=get_posts(['post_type'=>LeadRepository::POST_TYPE,'posts_per_page'=>-1,'fields'=>'ids']); $r=['new'=>0,'contacted'=>0,'qualified'=>0,'quotation_sent'=>0,'negotiating'=>0,'won'=>0,'lost'=>0,'overdue'=>0]; foreach($ids as $id){if($userId && (int)get_post_meta($id,'_pwt_lead_assigned_to',true)!==$userId)continue;$s=(string)get_post_meta($id,'_pwt_lead_status',true);if(isset($r[$s]))$r[$s]++;$d=(string)get_post_meta($id,'_pwt_lead_follow_up_date',true);if($d!==''&&strtotime($d)<strtotime(current_time('Y-m-d'))&&!in_array($s,['won','lost'],true))$r['overdue']++;} return $r; } }
