<?php
declare(strict_types=1);
namespace PWT\Logging;
defined('ABSPATH') || exit;

use PWT\Core\Database\Schema;

final class AuditLog
{
    public function record(string $entityType, int $entityId, string $action, array $context = []): int
    {
        global $wpdb;
        $now = current_time('mysql');

        $wpdb->insert(Schema::tables()['audit'], [
            'entity_type' => sanitize_key($entityType),
            'entity_id' => $entityId,
            'action' => sanitize_key($action),
            'actor' => $this->actor((string)($context['actor'] ?? '')),
            'from_value' => isset($context['from']) ? wp_json_encode($context['from']) : null,
            'to_value' => isset($context['to']) ? wp_json_encode($context['to']) : null,
            'notes' => sanitize_textarea_field((string)($context['notes'] ?? '')),
            'created_at' => $now,
        ]);

        return (int)$wpdb->insert_id;
    }

    public function forEntity(string $entityType, int $entityId, int $limit = 50): array
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . Schema::tables()['audit'] . "
             WHERE entity_type=%s AND entity_id=%d ORDER BY id DESC LIMIT %d",
            sanitize_key($entityType),
            $entityId,
            max(1, $limit)
        ), ARRAY_A) ?: [];
    }

    /**
     * Persist the generic pwt/log action into the audit trail.
     */
    public function persistLog(string $level, string $message, array $context = []): void
    {
        $this->record(
            (string)($context['entity_type'] ?? 'system'),
            absint($context['entity_id'] ?? 0),
            'log.' . sanitize_key($level),
            [
                'to' => ['message' => $message],
                'notes' => wp_json_encode($context),
                'actor' => $context['actor'] ?? '',
            ]
        );
    }

    private function actor(string $provided): string
    {
        if ($provided !== '') {
            return sanitize_user($provided);
        }

        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            return $user->user_login ?: 'system';
        }

        return 'system';
    }
}