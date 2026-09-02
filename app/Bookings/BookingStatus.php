<?php
declare(strict_types=1);
namespace PWT\Bookings;
defined('ABSPATH') || exit;

final class BookingStatus
{
    public const PENDING = 'pending';
    public const HELD = 'held';
    public const CONFIRMED = 'confirmed';
    public const PAID = 'paid';
    public const CANCELLED = 'cancelled';
    public const REFUNDED = 'refunded';

    private const TRANSITIONS = [
        'pending'   => ['held', 'confirmed', 'paid', 'cancelled'],
        'held'      => ['pending', 'confirmed', 'paid', 'cancelled'],
        'confirmed' => ['paid', 'cancelled', 'refunded'],
        'paid'      => ['refunded', 'cancelled'],
        'cancelled' => [],
        'refunded'  => [],
    ];

    public static function all(): array
    {
        return array_keys(self::TRANSITIONS);
    }

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public static function labels(): array
    {
        return [
            'pending'   => __('Pending', 'wildtours-plugin'),
            'held'      => __('Hold', 'wildtours-plugin'),
            'confirmed' => __('Confirmed', 'wildtours-plugin'),
            'paid'      => __('Paid', 'wildtours-plugin'),
            'cancelled' => __('Cancelled', 'wildtours-plugin'),
            'refunded'  => __('Refunded', 'wildtours-plugin'),
        ];
    }

    public static function label(string $status): string
    {
        return self::labels()[sanitize_key($status)] ?? ucfirst(sanitize_key($status));
    }
}