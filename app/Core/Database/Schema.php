<?php
declare(strict_types=1);
namespace PWT\Core\Database;
defined('ABSPATH') || exit;

final class Schema
{
    public const VERSION = '2.3.0';

    public static function tables(): array
    {
        global $wpdb;
        return [
            'customers'    => $wpdb->prefix . 'pwt_customers',
            'bookings'     => $wpdb->prefix . 'pwt_bookings',
            'items'        => $wpdb->prefix . 'pwt_booking_items',
            'availability' => $wpdb->prefix . 'pwt_availability',
            'rates'        => $wpdb->prefix . 'pwt_rates',
            'payments'     => $wpdb->prefix . 'pwt_payments',
            'holds'        => $wpdb->prefix . 'pwt_inventory_holds',
        ];
    }

    public static function install(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $c = $wpdb->get_charset_collate();
        $t = self::tables();

        $sql = [];
        $sql[] = "CREATE TABLE {$t['customers']} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            wp_user_id BIGINT UNSIGNED NULL,
            email VARCHAR(190) NULL,
            phone VARCHAR(50) NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NULL,
            country VARCHAR(100) NULL,
            city VARCHAR(100) NULL,
            notes TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY wp_user_id (wp_user_id),
            KEY email (email),
            KEY phone (phone)
        ) $c;";

        $sql[] = "CREATE TABLE {$t['bookings']} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            legacy_post_id BIGINT UNSIGNED NULL,
            customer_id BIGINT UNSIGNED NULL,
            booking_number VARCHAR(40) NOT NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'pending',
            travel_start DATE NULL,
            travel_end DATE NULL,
            adults INT UNSIGNED NOT NULL DEFAULT 1,
            children INT UNSIGNED NOT NULL DEFAULT 0,
            currency VARCHAR(3) NOT NULL DEFAULT 'INR',
            subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
            discount DECIMAL(12,2) NOT NULL DEFAULT 0,
            tax DECIMAL(12,2) NOT NULL DEFAULT 0,
            total DECIMAL(12,2) NOT NULL DEFAULT 0,
            deposit_due DECIMAL(12,2) NOT NULL DEFAULT 0,
            notes TEXT NULL,
            source VARCHAR(40) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY booking_number (booking_number),
            KEY legacy_post_id (legacy_post_id),
            KEY customer_id (customer_id),
            KEY status (status),
            KEY travel_start (travel_start)
        ) $c;";

        $sql[] = "CREATE TABLE {$t['items']} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_id BIGINT UNSIGNED NOT NULL,
            item_type VARCHAR(40) NOT NULL,
            object_id BIGINT UNSIGNED NULL,
            name VARCHAR(255) NOT NULL,
            quantity INT UNSIGNED NOT NULL DEFAULT 1,
            start_date DATE NULL,
            end_date DATE NULL,
            unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
            total DECIMAL(12,2) NOT NULL DEFAULT 0,
            meta LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY booking_id (booking_id),
            KEY object_lookup (item_type, object_id)
        ) $c;";

        $sql[] = "CREATE TABLE {$t['availability']} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            resource_type VARCHAR(40) NOT NULL,
            resource_id BIGINT UNSIGNED NOT NULL,
            service_date DATE NOT NULL,
            capacity INT UNSIGNED NOT NULL DEFAULT 0,
            reserved INT UNSIGNED NOT NULL DEFAULT 0,
            blocked INT UNSIGNED NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'open',
            notes TEXT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY resource_day (resource_type, resource_id, service_date),
            KEY service_date (service_date)
        ) $c;";

        $sql[] = "CREATE TABLE {$t['rates']} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            resource_type VARCHAR(40) NOT NULL,
            resource_id BIGINT UNSIGNED NOT NULL,
            season_id BIGINT UNSIGNED NULL,
            start_date DATE NULL,
            end_date DATE NULL,
            rate_type VARCHAR(30) NOT NULL DEFAULT 'base',
            amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            currency VARCHAR(3) NOT NULL DEFAULT 'INR',
            min_quantity INT UNSIGNED NOT NULL DEFAULT 1,
            max_quantity INT UNSIGNED NULL,
            priority INT NOT NULL DEFAULT 10,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY resource_dates (resource_type, resource_id, start_date, end_date),
            KEY season_id (season_id)
        ) $c;";

        $sql[] = "CREATE TABLE {$t['payments']} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_id BIGINT UNSIGNED NOT NULL,
            gateway VARCHAR(40) NOT NULL,
            transaction_type VARCHAR(20) NOT NULL DEFAULT 'payment',
            transaction_id VARCHAR(190) NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'pending',
            amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            currency VARCHAR(3) NOT NULL DEFAULT 'INR',
            reference VARCHAR(190) NULL,
            paid_at DATETIME NULL,
            meta LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY booking_id (booking_id),
            KEY transaction_id (transaction_id),
            KEY status (status),
            KEY transaction_type (transaction_type)
        ) $c;";

        $sql[] = "CREATE TABLE {$t['holds']} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_id BIGINT UNSIGNED NOT NULL,
            resource_id BIGINT UNSIGNED NOT NULL,
            resource_type VARCHAR(40) NOT NULL,
            service_date DATE NOT NULL,
            quantity INT UNSIGNED NOT NULL DEFAULT 1,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY booking_id (booking_id),
            KEY resource_day (resource_type, resource_id, service_date),
            KEY status_expiry (status, expires_at)
        ) $c;";

        foreach ($sql as $statement) {
            dbDelta($statement);
        }

        update_option('pwt_schema_version', self::VERSION, false);
    }
}
