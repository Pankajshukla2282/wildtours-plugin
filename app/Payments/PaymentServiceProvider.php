<?php
declare(strict_types=1);
namespace PWT\Payments;
defined('ABSPATH') || exit;

use PWT\Core\ServiceProvider;

final class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(PaymentRepository::class, PaymentRepository::class);
        $this->singleton(PaymentService::class, PaymentService::class);
    }
    public function boot(): void
    {
        $this->make(WebhookController::class)->register();
        $this->make(PaymentManager::class)->register();
    }
}
