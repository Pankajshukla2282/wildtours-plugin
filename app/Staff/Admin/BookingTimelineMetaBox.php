<?php
declare(strict_types=1);
namespace PWT\Staff\Admin;
defined('ABSPATH') || exit;
use PWT\Staff\Timeline\TimelineRepository;
use PWT\Staff\Automation\BookingAutomation;
final class BookingTimelineMetaBox {
 public function __construct(private TimelineRepository $timeline, private BookingAutomation $automation){}
 public function register(): void { add_action('add_meta_boxes',[$this,'boxes']); }
 public function boxes(): void { add_meta_box('pwt-booking-timeline',__('PWT Operations Timeline & Readiness','wildtours-plugin'),[$this,'render'],'pwt_booking','normal','default'); }
 public function render(\WP_Post $post): void { $v=$this->automation->validateReady($post->ID); $pct=$v['total']?round(($v['completed']/$v['total'])*100):0; echo '<p><strong>'.esc_html(sprintf(__('Operations completion: %d%%','wildtours-plugin'),$pct)).'</strong> &mdash; '.($v['ready']?'<span style="color:green">READY FOR TRAVEL</span>':'<span style="color:#b32d2e">Pending operational work</span>').'</p>'; echo '<p>'.esc_html(sprintf('Tasks: %d | Completed: %d | Blocked: %d | Overdue: %d',$v['total'],$v['completed'],$v['blocked'],$v['overdue'])).'</p><hr>'; foreach($this->timeline->events($post->ID) as $e){$at=(string)get_post_meta($e->ID,'_pwt_event_at',true); echo '<div style="margin:10px 0;padding:8px;border-left:3px solid #2271b1"><strong>'.esc_html($e->post_title).'</strong><br><small>'.esc_html($at).'</small><br>'.wp_kses_post(wpautop($e->post_content)).'</div>'; } }
}
