<?php
declare(strict_types=1);
namespace PWT\Availability;
defined('ABSPATH') || exit;
use PWT\Core\Database\Schema;
final class OperationalCalendar {
 public function range(string $from,string $to,?string $type=null,?int $resourceId=null): array { global $wpdb;$t=Schema::tables();$sql="SELECT a.*, COALESCE((SELECT SUM(h.quantity) FROM {$t['holds']} h WHERE h.resource_type=a.resource_type AND h.resource_id=a.resource_id AND h.service_date=a.service_date AND h.status='active' AND h.expires_at>UTC_TIMESTAMP()),0) held FROM {$t['availability']} a WHERE a.service_date BETWEEN %s AND %s";$args=[$from,$to];if($type!==null){$sql.=" AND a.resource_type=%s";$args[]=sanitize_key($type);}if($resourceId!==null){$sql.=" AND a.resource_id=%d";$args[]=$resourceId;}$sql.=" ORDER BY a.service_date,a.resource_type,a.resource_id";$rows=$wpdb->get_results($wpdb->prepare($sql,...$args),ARRAY_A)?:[];foreach($rows as &$r){$r['remaining']=max(0,(int)$r['capacity']-(int)$r['reserved']-(int)$r['blocked']-(int)$r['held']);}return $rows; }
}
