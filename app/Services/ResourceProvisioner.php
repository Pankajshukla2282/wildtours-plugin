<?php
declare(strict_types=1);
namespace PWT\Services;
defined('ABSPATH') || exit;

use PWT\Availability\AvailabilityRepository;

final class ResourceProvisioner
{
    public function __construct(private readonly AvailabilityRepository $availability) {}

    public function provisionRoomUnit(int $unitId, string $startDate, string $endDate, int $capacity = 1): int
    {
        return $this->provision($unitId, 'room_unit', $startDate, $endDate, max(1, $capacity));
    }

    public function provisionSafariSchedule(int $scheduleId, string $date, int $capacity): int
    {
        return $this->provision($scheduleId, 'safari_schedule', $date, $date, max(1, $capacity));
    }

    /**
     * Set (or block) a single day for an arbitrary resource.
     */
    public function provisionDay(int $resourceId, string $type, string $date, int $capacity, bool $block = false): bool
    {
        $capacity = max(0, $capacity);
        return $this->availability->set(
            $resourceId,
            sanitize_key($type),
            sanitize_text_field($date),
            $capacity,
            0,
            0,
            $block ? 'blocked' : 'open'
        );
    }

    /**
     * Provision capacity for every published room unit (next $days days) and
     * every published safari schedule (on its service date). Returns the total
     * number of day-rows created or refreshed.
     */
    public function syncAll(int $days = 365, string $fromDate = ''): int
    {
        $from = $fromDate !== '' ? $fromDate : current_time('Y-m-d');
        $total = 0;

        foreach ($this->publishedRoomUnits() as $unitId) {
            $total += $this->provisionRoomUnit((int) $unitId, $from, gmdate('Y-m-d', strtotime($from . ' +' . max(1, $days) . ' days')));
        }

        foreach ($this->publishedSafariSchedules() as $scheduleId) {
            $date = (string) get_post_meta((int) $scheduleId, 'service_date', true);
            if ($date === '') {
                continue;
            }
            $capacity = max(1, (int) get_post_meta((int) $scheduleId, 'capacity', true));
            $total += $this->provisionSafariSchedule((int) $scheduleId, $date, $capacity);
        }

        return $total;
    }

    /**
     * @return int[]
     */
    public function publishedRoomUnits(): array
    {
        return array_map('absint', get_posts([
            'post_type' => 'pwt_room_unit',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]));
    }

    /**
     * @return int[]
     */
    public function publishedSafariSchedules(): array
    {
        return array_map('absint', get_posts([
            'post_type' => 'pwt_safari_schedule',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]));
    }

    private function provision(int $resourceId, string $type, string $start, string $end, int $capacity): int
    {
        $startTs = strtotime($start);
        $endTs = strtotime($end);
        if (!$startTs || !$endTs || $endTs < $startTs) {
            return 0;
        }

        $count = 0;
        for ($ts = $startTs; $ts <= $endTs; $ts += DAY_IN_SECONDS) {
            $this->availability->set($resourceId, $type, gmdate('Y-m-d', $ts), $capacity);
            $count++;
        }
        return $count;
    }
}
