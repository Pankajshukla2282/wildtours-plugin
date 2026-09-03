<?php
declare(strict_types=1);
namespace PWT\Staff\Operations;
defined('ABSPATH') || exit;

final class OperationsRepository
{
    const META_ASSIGNEE = '_pwt_ops_assignee';
    const META_STATUS = '_pwt_ops_status';
    const META_DUE = '_pwt_ops_due_date';
    const META_NOTES = '_pwt_ops_notes';
    const META_UPDATED = '_pwt_ops_updated_at';
    const META_UPDATED_BY = '_pwt_ops_updated_by';
    const ASSIGNMENT_KEYS = ['booking', 'safari', 'accommodation', 'transport'];

    public function get(int $bookingId): array {
        $out = [];
        foreach (self::ASSIGNMENT_KEYS as $key) {
            $out[$key] = (int)get_post_meta($bookingId, self::META_ASSIGNEE . '_' . $key, true);
        }
        $transientKey = 'pwt_ops_booking_' . $bookingId;
        $cached = get_transient($transientKey);
        if ($cached !== false) {
            return $cached;
        }
        $cached['status'] = (string)(get_post_meta($bookingId, self::META_STATUS, true) ?: 'new');
        $cached['due_date'] = (string)get_post_meta($bookingId, self::META_DUE, true);
        $cached['notes'] = (string)get_post_meta($bookingId, self::META_NOTES, true);
        set_transient($transientKey, $cached, HOUR_IN_SECONDS);
        return $cached;
    }

    public function save(int $bookingId, array $data, int $actor): void {
        foreach (self::ASSIGNMENT_KEYS as $key) {
            if (array_key_exists($key, $data['assignees'] ?? [])) {
                update_post_meta($bookingId, self::META_ASSIGNEE . '_' . $key, (int)$data['assignees'][$key]);
            }
        }
        foreach (['status' => self::META_STATUS, 'due_date' => self::META_DUE, 'notes' => self::META_NOTES] as $field => $meta) {
            if (array_key_exists($field, $data)) {
                update_post_meta($bookingId, $meta, sanitize_textarea_field((string)$data[$field]));
            }
        }
        update_post_meta($bookingId, self::META_UPDATED, current_time('mysql'));
        update_post_meta($bookingId, self::META_UPDATED_BY, $actor);
    }

    public function assignedBookings(int $userId): array {
        $meta = [];
        foreach (self::ASSIGNMENT_KEYS as $key) {
            $meta[] = ['key' => self::META_ASSIGNEE . '_' . $key, 'value' => $userId, 'compare' => '='];
        }
        $transientKey = 'pwt_ops_assigned_' . $userId;
        $cached = get_transient($transientKey);
        if ($cached !== false) {
            return $cached;
        }

        $result = get_posts([
            'post_type' => 'pwt_booking',
            'post_status' => 'any',
            'numberposts' => 100,
            'orderby' => 'modified',
            'order' => 'DESC',
            'meta_query' => array_merge(['relation' => 'OR'], $meta)
        ]);

        set_transient($transientKey, $result, HOUR_IN_SECONDS);
        return $result;
    }
}
