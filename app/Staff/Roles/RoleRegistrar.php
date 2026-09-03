<?php
declare(strict_types=1);
namespace PWT\Staff\Roles;
defined('ABSPATH') || exit;

final class RoleRegistrar
{
    public const ROLES = [
        'pwt_operations_manager' => 'Operations Manager',
        'pwt_booking_executive' => 'Booking Executive',
        'pwt_safari_coordinator' => 'Safari Coordinator',
        'pwt_accommodation_coordinator' => 'Accommodation Coordinator',
        'pwt_transport_coordinator' => 'Transport Coordinator',
        'pwt_vendor_manager' => 'Vendor Manager',
        'pwt_accounts_executive' => 'Accounts Executive',
        'pwt_content_executive' => 'Content Executive',
    ];

    public static function register(): void
    {
        $base = ['read' => true, Capabilities::ACCESS_DASHBOARD => true];
        $map = [
            'pwt_operations_manager' => array_merge($base, Capabilities::operationsManager()),
            'pwt_booking_executive' => array_merge($base, Capabilities::bookingExecutive()),
            'pwt_safari_coordinator' => array_merge($base, Capabilities::safariCoordinator()),
            'pwt_accommodation_coordinator' => array_merge($base, Capabilities::accommodationCoordinator()),
            'pwt_transport_coordinator' => array_merge($base, Capabilities::transportCoordinator()),
            'pwt_vendor_manager' => array_merge($base, Capabilities::vendorManager()),
            'pwt_accounts_executive' => array_merge($base, Capabilities::accountsExecutive()),
            'pwt_content_executive' => array_merge($base, Capabilities::contentExecutive()),
        ];
        foreach (self::ROLES as $slug => $name) {
            remove_role($slug);
            add_role($slug, __($name, 'wildtours-plugin'), $map[$slug]);
        }
        if ($admin = get_role('administrator')) {
            foreach (Capabilities::all() as $cap) {
                $admin->add_cap($cap);
            }
        }
    }

    public static function labelFor(string $role): string { return self::ROLES[$role] ?? $role; }
    public static function isStaffRole(string $role): bool { return isset(self::ROLES[$role]); }
}
