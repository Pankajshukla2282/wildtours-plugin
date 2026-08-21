<?php
declare(strict_types=1);
namespace PWT\Integrations;
defined('ABSPATH') || exit;

use PWT\Bookings\Repositories\BookingDataRepository;
use PWT\Bookings\Repositories\BookingItemRepository;
use PWT\Availability\AvailabilityRepository;
use PWT\Core\Database\Schema;
use PWT\Customers\CustomerRepository;

/**
 * One-way import adapter for the wpdevelop Booking Calendar plugin.
 *
 * pwt_availability remains the single system of record. This adapter pulls
 * bookings that staff entered in Booking Calendar into the normalized
 * pwt_bookings table so they appear in the operations dashboard. Availability
 * is only reserved when the admin has configured a resource mapping
 * (pwt_settings.bc_resource_map: bc_resource_id => ["item_type", "item_id"]).
 */
final class BookingCalendarSync
{
    public function __construct(
        private readonly BookingDataRepository $bookings,
        private readonly BookingItemRepository $items,
        private readonly AvailabilityRepository $availability,
        private readonly CustomerRepository $customers
    ) {
    }

    /**
     * Check if FluentBooking is active and return its hook names.
     *
     * @return array 'active' => bool, 'appointment_created' => string|false, 'appointment_updated' => string|false
     */
    public static function fluentBookingStatus(): array
    {
        $active = class_exists('fluentbooking') || function_exists('fluent_booking_get_appointments');

        $appointmentCreated = $active ? 'fluent_booking_appointment_created' : false;
        $appointmentUpdated = $active ? 'fluent_booking_appointment_updated' : false;

        return [
            'active' => $active,
            'appointment_created' => $appointmentCreated,
            'appointment_updated' => $appointmentUpdated,
        ];
    }

    public function detected(): bool
    {
        global $wpdb;
        return class_exists('wpdevbk')
            || function_exists('wpbc_parse_params')
            || (bool) $wpdb->get_var($wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $wpdb->prefix . 'booking'
            ));
    }

    /**
     * Import recent Booking Calendar bookings as normalized bookings.
     * Returns the number of newly imported bookings.
     */
    public function importRecent(int $limit = 50): int
    {
        global $wpdb;
        if (!$this->detected()) {
            return 0;
        }

        $bookingTable = $wpdb->prefix . 'booking';
        $datesTable = $wpdb->prefix . 'bookingdates';
        $tables = array_map(static fn (string $t): bool => (bool) $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $t)), [$bookingTable, $datesTable]);
        if (in_array(false, $tables, true)) {
            return 0;
        }

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT b.booking_id AS bc_id, b.create_date, b.user, b.status, b.cost, b.remark, b.type
                 FROM {$bookingTable} b
                 LEFT JOIN {$datesTable} d ON d.booking_id = b.booking_id
                 GROUP BY b.booking_id
                 ORDER BY b.create_date DESC
                 LIMIT %d",
                max(1, $limit)
            ),
            ARRAY_A
        ) ?: [];

        $imported = 0;
        $map = get_option('pwt_bc_bookings', []);
        foreach ($rows as $row) {
            $bcId = (int) ($row['bc_id'] ?? 0);
            if (!$bcId || isset($map[$bcId])) {
                continue;
            }

            $dates = $wpdb->get_col($wpdb->prepare(
                "SELECT booking_date FROM {$datesTable} WHERE booking_id=%d ORDER BY booking_date ASC",
                $bcId
            )) ?: [];

            $parsed = $this->parseForm((string) ($row['type'] ?? ''));
            $customerId = 0;
            $email = $parsed['email'] ?? '';
            if ($email !== '') {
                $customerId = $this->customers->findOrCreate([
                    'email' => $email,
                    'first_name' => $parsed['first_name'] ?? ($row['user'] ?? 'Booking Calendar Guest'),
                    'last_name' => $parsed['last_name'] ?? '',
                    'phone' => $parsed['phone'] ?? '',
                ]);
            }

            $bookingId = $this->bookings->create([
                'legacy_post_id' => null,
                'customer_id' => $customerId ?: null,
                'status' => $this->isApproved((string) ($row['status'] ?? '')) ? 'confirmed' : 'pending',
                'travel_start' => $dates[0] ?? gmdate('Y-m-d'),
                'travel_end' => $dates[count($dates) - 1] ?? ($dates[0] ?? gmdate('Y-m-d')),
                'adults' => max(1, (int) ($parsed['persons'] ?? 1)),
                'total' => (float) ($row['cost'] ?? 0),
                'notes' => sanitize_textarea_field((string) ($row['remark'] ?? '')),
                'source' => 'booking_calendar',
            ]);

            if ($bookingId) {
                $map[$bcId] = $bookingId;
                $this->importItems($bookingId, $bcId, $dates);
                $imported++;
            }
        }

        update_option('pwt_bc_bookings', $map, false);
        return $imported;
    }

    public function resourceMapping(): array
    {
        $settings = (array) get_option('pwt_settings', []);
        return is_array($settings['bc_resource_map'] ?? null) ? $settings['bc_resource_map'] : [];
    }

    private function importItems(int $bookingId, int $bcId, array $dates): void
    {
        $mapping = $this->resourceMapping();
        if (!$mapping) {
            return;
        }

        // Booking Calendar resources are per date-range; use the earliest date.
        $date = (string) ($dates[0] ?? '');
        if ($date === '') {
            return;
        }

        foreach ($mapping as $bcResourceId => $target) {
            if (!is_array($target) || count($target) < 2) {
                continue;
            }
            $itemType = sanitize_key((string) $target[0]);
            $itemId = absint($target[1]);
            if (!$itemType || !$itemId) {
                continue;
            }

            $created = $this->items->create([
                'booking_id' => $bookingId,
                'item_type' => $itemType,
                'item_id' => $itemId,
                'name' => ucfirst($itemType) . ' #' . $itemId,
                'quantity' => 1,
                'start_date' => $date,
                'end_date' => $date,
                'meta' => ['bc_booking_id' => $bcId, 'bc_resource_id' => $bcResourceId],
            ]);

            if ($created && $this->availability->check($itemId, $itemType, $date, 1)['available']) {
                $this->availability->reserve($itemId, $itemType, $date, 1);
            }
        }
    }

    private function isApproved(string $status): bool
    {
        return $status === '' || in_array(strtolower($status), ['active', 'approved', 'confirmed'], true);
    }

    /**
     * wpdevelop serializes form fields as an array in the "type" column.
     * Extract common fields defensively.
     */
    private function parseForm(string $type): array
    {
        $data = [];
        if ($type !== '') {
            $decoded = @unserialize($type, ['allowed_classes' => false]);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }
        return [
            'email' => sanitize_email((string) ($data['email'] ?? '')),
            'first_name' => sanitize_text_field((string) ($data['firstname'] ?? $data['name'] ?? '')),
            'last_name' => sanitize_text_field((string) ($data['secondname'] ?? '')),
            'phone' => sanitize_text_field((string) ($data['phone'] ?? '')),
            'persons' => max(1, (int) ($data['visitors'] ?? $data['persons'] ?? 1)),
        ];
    }
}