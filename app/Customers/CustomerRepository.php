<?php
declare(strict_types=1);
namespace PWT\Customers;
defined('ABSPATH') || exit;
use PWT\Core\Database\Schema;

final class CustomerRepository
{
    public function find(int $id): array
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . Schema::tables()['customers'] . " WHERE id=%d", absint($id)), ARRAY_A);
        return $row ?: [];
    }

    /** @return array<int,array<string,mixed>> */
    public function search(string $term = '', int $limit = 100): array
    {
        global $wpdb;
        $table = Schema::tables()['customers'];
        $limit = max(1, min(500, $limit));
        $term = sanitize_text_field($term);
        if ($term === '') {
            return $wpdb->get_results("SELECT * FROM {$table} ORDER BY updated_at DESC LIMIT {$limit}", ARRAY_A) ?: [];
        }
        $like = '%' . $wpdb->esc_like($term) . '%';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE first_name LIKE %s OR last_name LIKE %s OR email LIKE %s OR phone LIKE %s ORDER BY updated_at DESC LIMIT {$limit}",
            $like, $like, $like, $like
        ), ARRAY_A) ?: [];
    }

    public function findOrCreate(array $data): int
    {
        global $wpdb;
        $table = Schema::tables()['customers'];
        $email = sanitize_email((string)($data['email'] ?? ''));
        $phone = sanitize_text_field((string)($data['phone'] ?? ''));
        if ($email !== '') {
            $id = (int)$wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE email=%s LIMIT 1", $email));
            if ($id) { $this->update($id, $data); return $id; }
        }
        if ($phone !== '') {
            $id = (int)$wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE phone=%s LIMIT 1", $phone));
            if ($id) { $this->update($id, $data); return $id; }
        }
        $now = current_time('mysql');
        $wpdb->insert($table, [
            'wp_user_id' => get_current_user_id() ?: null,
            'email' => $email ?: null,
            'phone' => $phone ?: null,
            'first_name' => sanitize_text_field((string)($data['first_name'] ?? $data['name'] ?? 'Guest')),
            'last_name' => sanitize_text_field((string)($data['last_name'] ?? '')),
            'country' => sanitize_text_field((string)($data['country'] ?? '')),
            'city' => sanitize_text_field((string)($data['city'] ?? '')),
            'notes' => sanitize_textarea_field((string)($data['notes'] ?? '')),
            'created_at' => $now, 'updated_at' => $now,
        ]);
        return (int)$wpdb->insert_id;
    }

    /** Partial updates preserve existing customer information. */
    public function update(int $id, array $data): bool
    {
        global $wpdb;
        $existing = $this->find($id);
        if (!$existing) return false;
        $field = static function(string $key, callable $sanitize) use ($data, $existing) {
            return array_key_exists($key, $data) ? $sanitize($data[$key]) : ($existing[$key] ?? null);
        };
        $payload = [
            'email' => ($v = $field('email', fn($v) => sanitize_email((string)$v))) ?: null,
            'phone' => ($v = $field('phone', fn($v) => sanitize_text_field((string)$v))) ?: null,
            'first_name' => sanitize_text_field((string)$field('first_name', fn($v) => $v)) ?: 'Guest',
            'last_name' => ($v = $field('last_name', fn($v) => sanitize_text_field((string)$v))) ?: null,
            'country' => ($v = $field('country', fn($v) => sanitize_text_field((string)$v))) ?: null,
            'city' => ($v = $field('city', fn($v) => sanitize_text_field((string)$v))) ?: null,
            'notes' => ($v = $field('notes', fn($v) => sanitize_textarea_field((string)$v))) ?: null,
            'updated_at' => current_time('mysql'),
        ];
        return false !== $wpdb->update(Schema::tables()['customers'], $payload, ['id' => $id], ['%s','%s','%s','%s','%s','%s','%s','%s'], ['%d']);
    }
}
