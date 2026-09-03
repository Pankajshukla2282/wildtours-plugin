<?php

namespace PWT\API;


use PWT\Core\ServiceProvider;

defined('ABSPATH') || exit;

class ApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        add_action('rest_api_init', function (): void {
            $this->make(ArchitectureRestApi::class)->register();
        });
        (new RestApi())->register();
    }
}
