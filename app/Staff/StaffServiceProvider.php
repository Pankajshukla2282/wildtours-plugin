<?php
declare(strict_types=1);
namespace PWT\Staff;
defined('ABSPATH') || exit;
use PWT\Core\ServiceProvider;
use PWT\Staff\Admin\StaffAdmin;
use PWT\Staff\Roles\RoleRegistrar;
use PWT\Staff\Services\StaffService;
use PWT\Staff\Admin\BookingOperationsMetaBox;
use PWT\Staff\Admin\OperationsDashboard;
use PWT\Staff\Operations\OperationsRepository;
use PWT\Staff\Operations\TaskRepository;
use PWT\Staff\Admin\OperationsTaskBoard;
use PWT\Staff\Timeline\TimelineRepository;
use PWT\Staff\Automation\BookingAutomation;
use PWT\Staff\Admin\BookingTimelineMetaBox;
use PWT\Staff\Admin\ManagerControlCentre;
use PWT\Staff\Trips\TripRepository;
use PWT\Staff\Admin\TripOperationsBoard;
use PWT\Staff\CRM\FeedbackRepository;
use PWT\Staff\CRM\ComplaintRepository;
use PWT\Staff\CRM\FollowUpService;
use PWT\Staff\CRM\CustomerLifecycleService;
use PWT\Staff\Vendors\VendorPerformanceService;
use PWT\Staff\Automation\PostTourAutomation;
use PWT\Staff\CRM\FollowUpRepository;
use PWT\Staff\CRM\CommunicationRepository;
use PWT\Staff\CRM\CustomerSegmentationService;
use PWT\Staff\Admin\CRMControlCentre;
use PWT\Sales\Leads\LeadRepository;
use PWT\Sales\Quotations\QuoteRepository;

final class StaffServiceProvider extends ServiceProvider
{
    public function register(): void { $this->singleton(StaffService::class, StaffService::class); $this->singleton(StaffAdmin::class, StaffAdmin::class); $this->singleton(OperationsRepository::class, OperationsRepository::class); $this->singleton(TaskRepository::class, TaskRepository::class); $this->singleton(OperationsTaskBoard::class, OperationsTaskBoard::class); $this->singleton(TimelineRepository::class, TimelineRepository::class); $this->singleton(BookingAutomation::class, BookingAutomation::class); $this->singleton(BookingTimelineMetaBox::class, BookingTimelineMetaBox::class); $this->singleton(BookingOperationsMetaBox::class, BookingOperationsMetaBox::class); $this->singleton(OperationsDashboard::class, OperationsDashboard::class); $this->singleton(ManagerControlCentre::class, ManagerControlCentre::class); $this->singleton(TripRepository::class, TripRepository::class); $this->singleton(TripOperationsBoard::class, TripOperationsBoard::class); $this->singleton(FeedbackRepository::class, FeedbackRepository::class); $this->singleton(ComplaintRepository::class, ComplaintRepository::class); $this->singleton(CustomerLifecycleService::class, CustomerLifecycleService::class); $this->singleton(VendorPerformanceService::class, VendorPerformanceService::class); $this->singleton(FollowUpService::class, FollowUpService::class); $this->singleton(PostTourAutomation::class, PostTourAutomation::class); $this->singleton(FollowUpRepository::class, FollowUpRepository::class); $this->singleton(CommunicationRepository::class, CommunicationRepository::class); $this->singleton(CustomerSegmentationService::class, CustomerSegmentationService::class); $this->singleton(CRMControlCentre::class, CRMControlCentre::class); $this->singleton(LeadRepository::class, LeadRepository::class); $this->singleton(QuoteRepository::class, QuoteRepository::class); }
    public function boot(): void { $this->make(TaskRepository::class)->register(); $this->make(TripRepository::class)->register(); $this->make(FeedbackRepository::class)->register(); $this->make(ComplaintRepository::class)->register(); $this->make(PostTourAutomation::class)->register(); $this->make(FollowUpRepository::class)->register(); $this->make(CommunicationRepository::class)->register(); $this->make(LeadRepository::class)->register(); $this->make(QuoteRepository::class)->register(); $this->make(TimelineRepository::class)->register(); $this->make(BookingAutomation::class)->register(); if (is_admin()) { $this->make(StaffAdmin::class)->register(); $this->make(BookingOperationsMetaBox::class)->register(); $this->make(BookingTimelineMetaBox::class)->register(); $this->make(OperationsDashboard::class)->register(); $this->make(OperationsTaskBoard::class)->register(); $this->make(ManagerControlCentre::class)->register(); $this->make(TripOperationsBoard::class)->register(); $this->make(CRMControlCentre::class)->register(); } }
    public static function activate(): void { RoleRegistrar::register(); }
}
