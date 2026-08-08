<?php
declare(strict_types=1);
namespace PWT\Bookings\Services;
defined('ABSPATH') || exit;
use PWT\Bookings\Repositories\BookingDataRepository;
use PWT\Customers\CustomerRepository;

final class BookingDataService
{
    public function __construct(
        private readonly CustomerRepository $customers,
        private readonly BookingDataRepository $bookings
    ) {}

    public function syncLegacyBooking(int $postId, array $data): int
    {
        $customerId = $this->customers->findOrCreate($data);
        return $this->bookings->create([
            'legacy_post_id' => $postId,
            'customer_id' => $customerId,
            'status' => $data['status'] ?? 'pending',
            'travel_start' => $data['travel_date'] ?? null,
            'travel_end' => $data['travel_date'] ?? null,
            'adults' => $data['persons'] ?? 1,
            'currency' => 'INR',
            'source' => $data['source'] ?? 'website',
            'notes' => $data['message'] ?? '',
        ]);
    }
}
