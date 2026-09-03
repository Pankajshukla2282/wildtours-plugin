<?php
declare(strict_types=1);
namespace PWT\Staff\Operations;
defined('ABSPATH') || exit;
final class TaskRepository {
 public const POST_TYPE='pwt_ops_task';
 public function register(): void { register_post_type(self::POST_TYPE,['label'=>'Operations Tasks','public'=>false,'show_ui'=>false,'supports'=>['title','editor']]); }
 public function create(array $d): int { $id=wp_insert_post(['post_type'=>self::POST_TYPE,'post_status'=>'publish','post_title'=>sanitize_text_field($d['title']??'Operations Task'),'post_content'=>sanitize_textarea_field($d['description']??'')]); if(is_wp_error($id)) return 0; foreach(['booking_id','assignee','priority','status','due_date','blocked_reason','created_by'] as $k) update_post_meta($id,'_pwt_task_'.$k,$d[$k]??''); update_post_meta($id,'_pwt_task_created_at',current_time('mysql')); return (int)$id; }
 public function update(int $id,array $d): void { foreach(['assignee','priority','status','due_date','blocked_reason'] as $k) if(array_key_exists($k,$d)) update_post_meta($id,'_pwt_task_'.$k,$d[$k]); if(($d['status']??'')==='completed') update_post_meta($id,'_pwt_task_completed_at',current_time('mysql')); }
 public function tasks(array $args=[]): array { $q=['post_type'=>self::POST_TYPE,'post_status'=>'publish','numberposts'=>200,'orderby'=>'date','order'=>'DESC']; $mq=[]; foreach(['booking_id','assignee','status'] as $k) if(!empty($args[$k])) $mq[]=['key'=>'_pwt_task_'.$k,'value'=>$args[$k]]; if($mq)$q['meta_query']=$mq; return get_posts($q); }
 public function comments(int $task): array { return get_comments(['post_id'=>$task,'status'=>'approve','type'=>'pwt_ops_task']); }
 public function comment(int $task,string $text,int $user): void { wp_insert_comment(['comment_post_ID'=>$task,'comment_content'=>sanitize_textarea_field($text),'user_id'=>$user,'comment_type'=>'pwt_ops_task','comment_approved'=>1]); }
}
