<?php
declare(strict_types=1);
namespace PWT\Staff\Automation;
defined('ABSPATH') || exit;
use PWT\Staff\CRM\FollowUpService;
final class PostTourAutomation {
 public function __construct(private FollowUpService $followups) {}
 public function register(): void { add_action('updated_post_meta',[$this,'watchTrip'],10,4); }
 public function watchTrip($metaId,$objectId,$metaKey,$value): void { if($metaKey!=='_pwt_trip_status'||$value!=='completed'||get_post_type($objectId)!=='pwt_trip_operation') return; if(get_post_meta($objectId,'_pwt_post_tour_automation_done',true)) return; update_post_meta($objectId,'_pwt_post_tour_automation_done','1'); $this->followups->createForCompletedTrip((int)$objectId); do_action('pwt_trip_completed',$objectId); }
}
