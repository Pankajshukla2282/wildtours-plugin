<?php
declare(strict_types=1);
namespace PWT\Payments;
defined('ABSPATH') || exit;
use PWT\Core\Database\Schema;

final class PaymentEventRepository
{
    public function claim(string $provider, string $eventId, string $payloadHash): bool
    {
        global $wpdb;
        $table = Schema::tables()['payment_events'];
        $now = current_time('mysql');
        $ok = $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$table} (provider,event_id,status,payload_hash,created_at,updated_at) VALUES (%s,%s,'received',%s,%s,%s)",
            sanitize_key($provider), sanitize_text_field($eventId), $payloadHash, $now, $now
        ));
        return (int)$ok === 1;
    }
    public function processed(string $provider, string $eventId): void { $this->set($provider,$eventId,'processed',null); }
    public function failed(string $provider, string $eventId, string $error): void { $this->set($provider,$eventId,'failed',$error); }
    private function set(string $provider,string $eventId,string $status,?string $error): void {
        global $wpdb; $now=current_time('mysql');
        $wpdb->update(Schema::tables()['payment_events'], ['status'=>$status,'processed_at'=>$status==='processed'?$now:null,'error_message'=>$error,'updated_at'=>$now], ['provider'=>sanitize_key($provider),'event_id'=>sanitize_text_field($eventId)]);
    }
}
