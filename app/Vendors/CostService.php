<?php
declare(strict_types=1);
namespace PWT\Vendors;
defined('ABSPATH') || exit;

use PWT\Core\Database\Schema;

/**
 * High-level vendor cost & margin helpers used by bookings and reports.
 */
final class CostService
{
    public function __construct(
        private readonly VendorRateRepository $rates,
        private readonly VendorRepository $vendors
    ) {
    }

    /**
     * Supplier cost + vendor attribution for a booking line.
     *
     * Falls back to a generic rate card (resource_id = 0) for the resource type,
     * then to a zero cost when no rate card matches.
     *
     * @return array{vendor_id: int|null, vendor_name: string, cost: float}
     */
    public function forResource(int $resourceId, string $resourceType, string $date, int $quantity): array
    {
        $rate = $this->rates->matching($resourceType, $resourceId, $date)
            ?? $this->rates->matching($resourceType, 0, $date);

        if (!$rate) {
            return ['vendor_id' => null, 'vendor_name' => '', 'cost' => 0.0];
        }

        $vendor = $this->vendors->find((int)$rate['vendor_id']);

        return [
            'vendor_id' => (int)$rate['vendor_id'],
            'vendor_name' => (string)($vendor['name'] ?? ''),
            'cost' => round((float)$rate['unit_price'] * max(1, $quantity), 2),
        ];
    }

    /**
     * Total supplier cost for a booking.
     */
    public function bookingCost(int $bookingId): float
    {
        global $wpdb;
        return (float)$wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(cost),0) FROM " . Schema::tables()['items'] . " WHERE booking_id=%d",
            $bookingId
        ));
    }

    /**
     * Outstanding payable to a vendor: delivered (confirmed/paid) costs minus settled amounts.
     */
    public function vendorOutstanding(int $vendorId): float
    {
        global $wpdb;
        $t = Schema::tables();
        $owed = (float)$wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(i.cost),0)
             FROM {$t['items']} i INNER JOIN {$t['bookings']} b ON b.id = i.booking_id
             WHERE i.vendor_id=%d AND b.status IN ('confirmed','paid')",
            $vendorId
        ));
        $settled = $this->settled($vendorId);
        return max(0, round($owed - $settled, 2));
    }

    /**
     * Total amount already settled to a vendor.
     */
    public function settled(int $vendorId): float
    {
        global $wpdb;
        return (float)$wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount),0) FROM " . Schema::tables()['settlements'] . " WHERE vendor_id=%d",
            $vendorId
        ));
    }
}