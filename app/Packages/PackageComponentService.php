<?php
declare(strict_types=1);
namespace PWT\Packages;
defined('ABSPATH') || exit;
final class PackageComponentService {
 public function components(int $packageId): array { $v=get_post_meta($packageId,'_pwt_package_components',true); return is_array($v)?array_values($v):[]; }
 public function save(int $packageId,array $components): bool { $clean=[]; foreach($components as $c){ if(!is_array($c))continue; $t=sanitize_key((string)($c['resource_type']??''));$id=absint($c['resource_id']??0);if(!$t||!$id)continue;$clean[]=['resource_type'=>$t,'resource_id'=>$id,'name'=>sanitize_text_field((string)($c['name']??'')),'quantity'=>max(1,absint($c['quantity']??1)),'offset_start'=>max(0,(int)($c['offset_start']??0)),'offset_end'=>max(0,(int)($c['offset_end']??0)),'required'=>!empty($c['required'])];} return update_post_meta($packageId,'_pwt_package_components',$clean)!==false; }
 public function expand(int $packageId,string $travelStart,string $travelEnd): array { $start=new \DateTimeImmutable($travelStart);$end=new \DateTimeImmutable($travelEnd?:$travelStart);$out=[];foreach($this->components($packageId) as $c){$s=$start->modify('+'.(int)$c['offset_start'].' days');$e=$start->modify('+'.(int)$c['offset_end'].' days');if($e>$end)$e=$end;$out[]=['item_type'=>$c['resource_type'],'object_id'=>(int)$c['resource_id'],'name'=>$c['name']?:get_the_title((int)$c['resource_id']),'quantity'=>(int)$c['quantity'],'start_date'=>$s->format('Y-m-d'),'end_date'=>$e->format('Y-m-d'),'meta'=>['package_id'=>$packageId,'required'=>(bool)$c['required']]];}return $out; }
}
