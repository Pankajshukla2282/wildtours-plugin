<?php

namespace PWT\Widgets;

use PWT\Core\ServiceProvider;

defined('ABSPATH') || exit;

class WidgetServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        add_action('widgets_init', static function (): void {
            register_widget(ContactWidget::class);
            register_widget(PackageFilterWidget::class);
        });
    }
}
