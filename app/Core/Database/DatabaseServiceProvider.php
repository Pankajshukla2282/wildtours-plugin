<?php
declare(strict_types=1);
namespace PWT\Core\Database;
defined('ABSPATH') || exit;
use PWT\Core\ServiceProvider;

final class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void {}
    public function boot(): void
    {
        add_action('admin_init', [$this, 'maybeUpgrade']);
    }
    public function maybeUpgrade(): void
    {
        if (get_option('pwt_schema_version') !== Schema::VERSION) {
            Schema::install();
        }
    }
}
