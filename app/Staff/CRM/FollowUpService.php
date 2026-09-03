<?php
declare(strict_types=1);
namespace PWT\Staff\CRM;
defined('ABSPATH') || exit;
use PWT\Staff\Operations\TaskRepository;
final class FollowUpService {
 public function __construct(private TaskRepository $tasks) {}
 public function createForCompletedTrip(int $tripId): void { $booking=(int)get_post_meta($tripId,'_pwt_trip_booking_id',true); if(!$booking) return; $this->tasks->create(['title'=>'Post-tour customer follow-up','booking_id'=>$booking,'priority'=>'normal','status'=>'open','description'=>'Contact the customer, collect feedback and request a review where appropriate.','due_date'=>gmdate('Y-m-d',strtotime('+2 days'))]); do_action('pwt_post_tour_followup_created',$tripId,$booking); }
}
