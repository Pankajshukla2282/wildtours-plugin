<?php
declare(strict_types=1);
namespace PWT\Vendors;
defined('ABSPATH') || exit;

use PWT\Core\ServiceProvider;

final class VendorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (is_admin() && current_user_can('manage_options')) {
            $this->make(VendorsAdminPage::class)->register();
        }
    }
}