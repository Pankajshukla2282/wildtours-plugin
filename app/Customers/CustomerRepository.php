<?php
declare(strict_types=1);
namespace PWT\Customers;
defined('ABSPATH') || exit;
use PWT\Core\Database\Schema;

final class CustomerRepository
{
    public function findOrCreate(array $data): int
    {
        global $wpdb;
        $table = Schema::tables()['customers'];
        $email = sanitize_email((string)($data['email'] ?? ''));
        $phone = sanitize_text_field((string)($data['phone'] ?? ''));

        if ($email !== '') {
            $id = (int)$wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE email=%s LIMIT 1", $email));
            if ($id) {
                $this->update($id, $data);
                return $id;
            }
        }
        if ($phone !== '') {
            $id = (int)$wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE phone=%s LIMIT 1", $phone));
            if ($id) {
                $this->update($id, $data);
                return $id;
            }
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
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return (int)$wpdb->insert_id;
    }

    public function update(int $id, array $data): bool
    {
        global $wpdb;
        return false !== $wpdb->update(
            Schema::tables()['customers'],
            [
                'email' => sanitize_email((string)($data['email'] ?? '')),
                'phone' => sanitize_text_field((string)($data['phone'] ?? '')),
                'first_name' => sanitize_text_field((string)($data['first_name'] ?? $data['name'] ?? '')),
                'last_name' => sanitize_text_field((string)($data['last_name'] ?? '')),
                'country' => sanitize_text_field((string)($data['country'] ?? '')),
                'city' => sanitize_text_field((string)($data['city'] ?? '')),
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $id],
            ['%s','%s','%s','%s','%s','%s','%s'],
            ['%d']
        );
    }
}
