<?php
declare(strict_types=1);
namespace PWT\Customers;
defined('ABSPATH') || exit;
use PWT\Core\ServiceProvider;

final class CustomerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(CustomerRepository::class, CustomerRepository::class);
    }
    public function boot(): void
    {
        $this->make(CustomerPortal::class)->register();
    }
}
