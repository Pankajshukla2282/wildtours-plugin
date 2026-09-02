<?php
declare(strict_types=1);
namespace PWT\Vendors;
defined('ABSPATH') || exit;

use PWT\Core\ServiceProvider;

final class VendorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(VendorRepository::class, VendorRepository::class);
        $this->singleton(VendorRateRepository::class, VendorRateRepository::class);
        $this->singleton(SettlementRepository::class, SettlementRepository::class);
        $this->singleton(CostService::class, CostService::class);
        $this->singleton(VendorAssignmentService::class, VendorAssignmentService::class);
    }

    public function boot(): void
    {
        if (is_admin() && current_user_can('manage_options')) {
            $this->make(VendorsAdminPage::class)->register();
        }
    }
}