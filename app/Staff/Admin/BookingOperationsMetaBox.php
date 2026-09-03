<?php
declare(strict_types=1);
namespace PWT\Staff\Admin;
defined('ABSPATH') || exit;
use PWT\Staff\Operations\OperationsRepository;
use PWT\Staff\Roles\RoleRegistrar;

final class BookingOperationsMetaBox {
 public function register(): void { add_action('add_meta_boxes',[$this,'boxes']); add_action('save_post_pwt_booking',[$this,'save'],10,3); }
 public function boxes(): void { add_meta_box('pwt-booking-operations',__('PWT Operations Handover','wildtours-plugin'),[$this,'render'],'pwt_booking','normal','high'); }
 private function staffOptions(int $selected=0): void { $users=get_users(['role__in'=>array_keys(RoleRegistrar::ROLES),'orderby'=>'display_name','order'=>'ASC']); echo '<option value="0">'.esc_html__('Unassigned','wildtours-plugin').'</option>'; foreach($users as $u) printf('<option value="%d" %s>%s — %s</option>',$u->ID,selected($selected,$u->ID,false),esc_html($u->display_name),esc_html(RoleRegistrar::labelFor((string)($u->roles[0]??'')))); }
 public function render(\WP_Post $post): void { $r=new OperationsRepository(); $d=$r->get($post->ID); wp_nonce_field('pwt_booking_operations','pwt_booking_operations_nonce'); echo '<p>'.esc_html__('Assign operational owners and track the booking handover.','wildtours-plugin').'</p><table class="form-table"><tbody>';
 foreach(['booking'=>__('Booking Executive','wildtours-plugin'),'safari'=>__('Safari Coordinator','wildtours-plugin'),'accommodation'=>__('Accommodation Coordinator','wildtours-plugin'),'transport'=>__('Transport Coordinator','wildtours-plugin')] as $k=>$label){ echo '<tr><th><label>'.esc_html($label).'</label></th><td><select name="pwt_ops_assignee['.esc_attr($k).']">'; $this->staffOptions((int)$d['assignees'][$k]); echo '</select></td></tr>'; }
 echo '<tr><th>'.esc_html__('Operational Status','wildtours-plugin').'</th><td><select name="pwt_ops_status">'; foreach(['new'=>'New','assigned'=>'Assigned','in_progress'=>'In Progress','ready'=>'Ready for Travel','completed'=>'Completed','blocked'=>'Blocked'] as $v=>$l) printf('<option value="%s" %s>%s</option>',esc_attr($v),selected($d['status'],$v,false),esc_html__($l,'wildtours-plugin')); echo '</select></td></tr>';
 echo '<tr><th>'.esc_html__('Due Date','wildtours-plugin').'</th><td><input type="date" name="pwt_ops_due_date" value="'.esc_attr($d['due_date']).'"></td></tr><tr><th>'.esc_html__('Operations Notes / Handover','wildtours-plugin').'</th><td><textarea class="large-text" rows="6" name="pwt_ops_notes">'.esc_textarea($d['notes']).'</textarea></td></tr></tbody></table>'; }
 public function save(int $postId,\WP_Post $post,bool $update): void { if(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE) return; if(!isset($_POST['pwt_booking_operations_nonce'])||!wp_verify_nonce((string)$_POST['pwt_booking_operations_nonce'],'pwt_booking_operations')) return; if(!current_user_can('edit_post',$postId)) return; $a=[]; foreach(OperationsRepository::ASSIGNMENT_KEYS as $k) $a[$k]=(int)($_POST['pwt_ops_assignee'][$k]??0); (new OperationsRepository())->save($postId,['assignees'=>$a,'status'=>sanitize_key((string)($_POST['pwt_ops_status']??'new')),'due_date'=>sanitize_text_field((string)($_POST['pwt_ops_due_date']??'')),'notes'=>sanitize_textarea_field((string)($_POST['pwt_ops_notes']??''))],get_current_user_id()); }
}
