<?php
declare(strict_types=1);
namespace PWT\Staff\Automation;
defined('ABSPATH') || exit;
use PWT\Staff\Operations\TaskRepository;
use PWT\Staff\Operations\OperationsRepository;
use PWT\Staff\Timeline\TimelineRepository;
final class BookingAutomation {
 private bool $running=false;
 public function __construct(private TaskRepository $tasks, private OperationsRepository $ops, private TimelineRepository $timeline){}
 public function register(): void { add_action('save_post_pwt_booking',[$this,'onSave'],30,3); }
 public function onSave(int $id,\WP_Post $post,bool $update): void { if($this->running || wp_is_post_revision($id) || wp_is_post_autosave($id)) return; $this->running=true; try { $d=$this->ops->get($id); $status=$d['status']??'new'; if(in_array($status,['assigned','in_progress','ready'],true)) $this->ensureTasks($id,$d); if($update) $this->timeline->add($id,'Booking operations updated','Operations record saved.','booking',get_current_user_id()); } finally {$this->running=false;} }
 private function ensureTasks(int $booking,array $d): void { if(get_post_meta($booking,'_pwt_ops_templates_created',true)) return; $map=['safari'=>['Confirm safari permit / schedule','high'],'accommodation'=>['Confirm accommodation and room inventory','high'],'transport'=>['Arrange pickup, drop and local transport','normal'],'booking'=>['Verify booking details and customer itinerary','normal']]; foreach($map as $key=>$spec){ $this->tasks->create(['title'=>$spec[0],'booking_id'=>$booking,'assignee'=>(int)($d['assignees'][$key]??0),'priority'=>$spec[1],'status'=>'open','due_date'=>$d['due_date']??'','created_by'=>get_current_user_id()]); } update_post_meta($booking,'_pwt_ops_templates_created',1); $this->timeline->add($booking,'Operations tasks created','Standard travel-operation checklist generated automatically.','automation',get_current_user_id()); }
 public function validateReady(int $booking): array { $tasks=$this->tasks->tasks(['booking_id'=>$booking]); $total=count($tasks); $done=0; $blocked=0; $overdue=0; $today=current_time('Y-m-d'); foreach($tasks as $t){$s=(string)get_post_meta($t->ID,'_pwt_task_status',true); if($s==='completed')$done++; if($s==='blocked')$blocked++; $due=(string)get_post_meta($t->ID,'_pwt_task_due_date',true); if($due && $due<$today && $s!=='completed')$overdue++;} return ['total'=>$total,'completed'=>$done,'blocked'=>$blocked,'overdue'=>$overdue,'ready'=>$total>0 && $done===$total && !$blocked && !$overdue]; }
}
