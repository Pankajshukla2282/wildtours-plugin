<?php
declare(strict_types=1);
namespace PWT\Logging;
defined('ABSPATH') || exit;

use PWT\Core\ServiceProvider;

final class LogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(AuditLog::class, AuditLog::class);
    }

    public function boot(): void
    {
        add_action('pwt/log', [$this->make(AuditLog::class), 'persistLog'], 10, 3);
    }
}