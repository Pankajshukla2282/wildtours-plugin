<?php
declare(strict_types=1);
namespace PWT\Sales;
defined('ABSPATH') || exit;
use PWT\Core\ServiceProvider; use PWT\Sales\Leads\LeadRepository; use PWT\Sales\Leads\LeadAssignmentService; use PWT\Sales\Quotations\QuoteRepository; use PWT\Sales\Quotations\QuoteConversionService; use PWT\Sales\Dashboard\SalesDashboardService; use PWT\Bookings\Repositories\BookingRepository;
final class SalesServiceProvider extends ServiceProvider { public function register(): void { $this->singleton(LeadRepository::class,LeadRepository::class);$this->singleton(QuoteRepository::class,QuoteRepository::class);$this->singleton(LeadAssignmentService::class,LeadAssignmentService::class);$this->singleton(SalesDashboardService::class,SalesDashboardService::class);$this->singleton(QuoteConversionService::class,function(){return new QuoteConversionService($this->make(BookingRepository::class));}); } public function boot(): void { $this->make(LeadRepository::class)->register();$this->make(QuoteRepository::class)->register(); } }
