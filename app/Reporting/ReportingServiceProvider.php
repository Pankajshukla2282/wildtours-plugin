<?php
declare(strict_types=1);
namespace PWT\Reporting;
defined('ABSPATH') || exit;

use PWT\Core\ServiceProvider;

final class ReportingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Reports page is now registered in Menu.php - skip to avoid duplicate
        if (is_admin() && current_user_can('manage_options')) {
            // Reports submenu is registered in Menu.php, intentionally skipped here
        }
    }
}
