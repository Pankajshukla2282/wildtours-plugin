<?php

declare(strict_types=1);

namespace PWT\Settings;

defined('ABSPATH') || exit;

use PWT\Core\ServiceProvider;

final class SettingsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->make(SettingsPage::class)->register();
    }
}