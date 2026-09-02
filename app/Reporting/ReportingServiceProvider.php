<?php
declare(strict_types=1);
namespace PWT\Reporting;
defined('ABSPATH') || exit;

use PWT\Core\ServiceProvider;

final class ReportingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (is_admin() && current_user_can('manage_options')) {
            $this->make(ReportingDashboard::class)->register();
        }
    }
}
