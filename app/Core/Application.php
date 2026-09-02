<?php

declare(strict_types=1);

namespace PWT\Core;

defined('ABSPATH') || exit;

use PWT\REST\BookingRestServiceProvider;
use PWT\Reporting\ReportingServiceProvider;
use PWT\Payments\PaymentServiceProvider;
use PWT\Availability\InventoryServiceProvider;
use PWT\Packages\PackageServiceProvider;
use PWT\Admin\AdminServiceProvider;
use PWT\Analytics\AnalyticsServiceProvider;
use PWT\API\ApiServiceProvider;
use PWT\Bookings\BookingServiceProvider;
use PWT\Frontend\FrontendServiceProvider;
use PWT\Integrations\IntegrationServiceProvider;
use PWT\PostTypes\PostTypeServiceProvider;
use PWT\SCF\SCFServiceProvider;
use PWT\Taxonomies\TaxonomyServiceProvider;
use PWT\Widgets\WidgetServiceProvider;
use PWT\Core\Database\DatabaseServiceProvider;
use PWT\Architecture\ArchitectureServiceProvider;
use PWT\Services\ServiceServiceProvider;
use PWT\Customers\CustomerServiceProvider;
use PWT\Pricing\PricingServiceProvider;
use PWT\Admin\OperationsServiceProvider;
use PWT\Logging\LogServiceProvider;
use PWT\Vendors\VendorServiceProvider;

/**
 * Plugin application.
 */
final class Application
{
    /**
     * @var array<class-string<ServiceProvider>>
     */
    private const PROVIDERS = [
        DatabaseServiceProvider::class,
        BookingRestServiceProvider::class,
        ReportingServiceProvider::class,
        PaymentServiceProvider::class,
        InventoryServiceProvider::class,
        PackageServiceProvider::class,
        ArchitectureServiceProvider::class,
        ServiceServiceProvider::class,
        CustomerServiceProvider::class,
        PricingServiceProvider::class,
        PostTypeServiceProvider::class,
        TaxonomyServiceProvider::class,
        SCFServiceProvider::class,
        BookingServiceProvider::class,
        FrontendServiceProvider::class,
        ApiServiceProvider::class,
        WidgetServiceProvider::class,
        AnalyticsServiceProvider::class,
        AdminServiceProvider::class,
        OperationsServiceProvider::class,
        IntegrationServiceProvider::class,
        LogServiceProvider::class,
        VendorServiceProvider::class,
    ];

    private Container $container;

    private bool $booted = false;

    public function __construct()
    {
        $this->container = new Container();
    }

    /**
     * Register the application lifecycle.
     */
    public function boot(): void
    {
        add_filter('user_has_cap', [self::class, 'grantOperationsCapability'], 10, 1);
        add_action('init', [$this, 'init'], 10);
    }

    /**
     * Grant the operations capability to any user with manage_options (admins).
     *
     * The capability is also added to the administrator role on activation, but
     * plugin upgrades/reinstalls do not re-run activation, so the runtime grant
     * keeps the Operations, Pricing, Customers and Availability admin pages
     * visible for admins regardless of activation history. The dedicated
     * pwt_staff role still relies on its explicit pwt_manage_operations cap.
     *
     * @param array<string, bool> $allcaps
     *
     * @return array<string, bool>
     */
    public static function grantOperationsCapability(array $allcaps): array
    {
        if (!empty($allcaps['manage_options'])) {
            $allcaps['pwt_manage_operations'] = true;
        }

        return $allcaps;
    }

    /**
     * Initialize the application.
     */
    public function init(): void
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;

        foreach (self::PROVIDERS as $provider) {
            $this->container->register($provider);
        }

        $this->container->boot();
    }
}