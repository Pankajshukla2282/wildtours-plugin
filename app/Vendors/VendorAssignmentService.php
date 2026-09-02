<?php
declare(strict_types=1);
namespace PWT\Vendors;
defined('ABSPATH') || exit;
use PWT\Bookings\BookingItemRepository;
use PWT\Logging\AuditLog;

final class VendorAssignmentService
{
    public function __construct(private readonly VendorRepository $vendors, private readonly BookingItemRepository $items, private readonly AuditLog $audit) {}
    public function assign(int $bookingId, int $itemId, int $vendorId, ?float $cost = null): bool
    {
        $vendor=$this->vendors->find($vendorId); $item=$this->items->find($itemId);
        if(!$vendor || ($vendor['status']??'')!=='active' || !$item || (int)$item['booking_id']!==$bookingId) return false;
        $data=['vendor_id'=>$vendorId,'vendor_name'=>(string)$vendor['name']];
        if($cost!==null) $data['cost']=max(0,round($cost,2));
        if(!$this->items->update($itemId,$data)) return false;
        $this->audit->record('booking',$bookingId,'booking.vendor_assigned',['to'=>['item_id'=>$itemId,'vendor_id'=>$vendorId,'vendor_name'=>$vendor['name'],'cost'=>$data['cost']??$item['cost']??0]]);
        return true;
    }
}
