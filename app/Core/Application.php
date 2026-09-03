<?php

declare(strict_types=1);

namespace PWT\Core;

defined('ABSPATH') || exit;

use PWT\REST\BookingRestServiceProvider;
use PWT\Reporting\ReportingServiceProvider;
use PWT\Payments\PaymentServiceProvider;
use PWT\Availability\InventoryServiceProvider;
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
use PWT\Staff\StaffServiceProvider;
use PWT\Sales\SalesServiceProvider;

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
        StaffServiceProvider::class,
        SalesServiceProvider::class,
        IntegrationServiceProvider::class,
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
        add_action('init', [$this, 'init'], 10);
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