<?php

namespace PWT\API;


use PWT\Core\ServiceProvider;

defined('ABSPATH') || exit;

class ApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->make(ArchitectureRestApi::class)->register();
        $this->make(RestApi::class)->register();
    }
}
