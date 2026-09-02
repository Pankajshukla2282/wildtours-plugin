<?php
declare(strict_types=1);
namespace PWT\Core\Database;
defined('ABSPATH') || exit;

final class Transaction
{
    private static bool $active = false;

    /**
     * Run a unit of work inside a DB transaction.
     *
     * Nested calls participate in the outermost transaction instead of
     * starting (and implicitly committing) a new one.
     *
     * Returning false, null or a WP_Error rolls back the transaction.
     *
     * @param callable(): mixed $work
     */
    public static function run(callable $work): mixed
    {
        global $wpdb;

        $outer = !self::$active;

        if ($outer) {
            $wpdb->query('START TRANSACTION');
            self::$active = true;
        }

        try {
            $result = $work();

            $failed = $result === false || $result === null || is_wp_error($result);

            if ($outer) {
                self::$active = false;
                $wpdb->query($failed ? 'ROLLBACK' : 'COMMIT');
            }

            return $result;
        } catch (\Throwable $e) {
            if ($outer) {
                self::$active = false;
                $wpdb->query('ROLLBACK');
            }

            throw $e;
        }
    }
}