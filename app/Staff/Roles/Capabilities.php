<?php
declare(strict_types=1);
namespace PWT\Staff\Roles;
defined('ABSPATH') || exit;

final class Capabilities
{
    public const ACCESS_DASHBOARD = 'pwt_access_staff_dashboard';
    public const MANAGE_STAFF = 'pwt_manage_staff';
    public const MANAGE_BOOKINGS = 'pwt_manage_bookings';
    public const VIEW_CUSTOMERS = 'pwt_view_customers';
    public const MANAGE_SAFARI = 'pwt_manage_safari_operations';
    public const MANAGE_ACCOMMODATION = 'pwt_manage_accommodation';
    public const MANAGE_TRANSPORT = 'pwt_manage_transport';
    public const MANAGE_VENDORS = 'pwt_manage_vendors';
    public const MANAGE_PAYMENTS = 'pwt_manage_payments';
    public const VIEW_REPORTS = 'pwt_view_reports';
    public const MANAGE_CONTENT = 'pwt_manage_travel_content';
    public const MANAGE_PRICING = 'pwt_manage_pricing';

    public static function all(): array { return [self::ACCESS_DASHBOARD,self::MANAGE_STAFF,self::MANAGE_BOOKINGS,self::VIEW_CUSTOMERS,self::MANAGE_SAFARI,self::MANAGE_ACCOMMODATION,self::MANAGE_TRANSPORT,self::MANAGE_VENDORS,self::MANAGE_PAYMENTS,self::VIEW_REPORTS,self::MANAGE_CONTENT,self::MANAGE_PRICING]; }
}
