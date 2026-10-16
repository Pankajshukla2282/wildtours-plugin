<?php

declare(strict_types=1);

namespace PWT\Admin;

defined('ABSPATH') || exit;

use PWT\Bookings\Repositories\BookingDataRepository;
use PWT\Bookings\Services\EmailService;
use PWT\Payments\PaymentManager;
use PWT\Payments\PaymentRepository;

final class PaymentReviewPage
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'menu'], 40);
        add_action('admin_post_pwt_review_payment', [$this, 'review']);
    }

    public function menu(): void
    {
        add_submenu_page(
            'pwt-dashboard',
            __('Payment Review', 'wildtours-plugin'),
            __('Payment Review', 'wildtours-plugin'),
            'pwt_manage_payments',
            'pwt-payments',
            [$this, 'render']
        );
    }

    public function render(): void
    {
        $this->requireAccess();
        $pendingBookings = get_posts([
            'post_type' => 'pwt_booking',
            'post_status' => 'publish',
            'posts_per_page' => 100,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => [[
                'key' => '_pwt_payment_status',
                'value' => 'verification_pending',
            ]],
        ]);
        $result = sanitize_key((string) ($_GET['payment_review'] ?? ''));
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Payment Review', 'wildtours-plugin'); ?></h1>
            <?php if ($result === 'approved') : ?>
                <div class="notice notice-success"><p><?php esc_html_e('Payment verified and booking status updated.', 'wildtours-plugin'); ?></p></div>
            <?php elseif ($result === 'rejected') : ?>
                <div class="notice notice-warning"><p><?php esc_html_e('Payment reference rejected. The guest can submit a corrected reference.', 'wildtours-plugin'); ?></p></div>
            <?php elseif ($result === 'stale') : ?>
                <div class="notice notice-info"><p><?php esc_html_e('This payment was already reviewed.', 'wildtours-plugin'); ?></p></div>
            <?php endif; ?>
            <p><?php esc_html_e('Verify each transfer against your bank or payment provider before approving it.', 'wildtours-plugin'); ?></p>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Guest', 'wildtours-plugin'); ?></th>
                        <th><?php esc_html_e('Package / Date', 'wildtours-plugin'); ?></th>
                        <th><?php esc_html_e('Amount', 'wildtours-plugin'); ?></th>
                        <th><?php esc_html_e('Method / Reference', 'wildtours-plugin'); ?></th>
                        <th><?php esc_html_e('Submitted', 'wildtours-plugin'); ?></th>
                        <th><?php esc_html_e('Review', 'wildtours-plugin'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$pendingBookings) : ?>
                        <tr><td colspan="6"><?php esc_html_e('No payment references are waiting for review.', 'wildtours-plugin'); ?></td></tr>
                    <?php endif; ?>
                    <?php foreach ($pendingBookings as $booking) : $bookingId = (int) $booking->ID; ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html((string) get_post_meta($bookingId, '_pwt_name', true)); ?></strong><br>
                                <?php echo esc_html((string) get_post_meta($bookingId, '_pwt_email', true)); ?><br>
                                <?php echo esc_html((string) get_post_meta($bookingId, '_pwt_phone', true)); ?>
                            </td>
                            <td>
                                <?php echo esc_html(get_the_title((int) get_post_meta($bookingId, '_pwt_package_id', true)) ?: __('Booking enquiry', 'wildtours-plugin')); ?><br>
                                <?php echo esc_html((string) get_post_meta($bookingId, '_pwt_travel_date', true)); ?>
                            </td>
                            <td><?php echo esc_html('INR ' . number_format_i18n((float) (get_post_meta($bookingId, '_pwt_payment_submitted_amount', true) ?: get_post_meta($bookingId, '_pwt_payment_due_amount', true)), 2)); ?></td>
                            <td>
                                <strong><?php echo esc_html((string) get_post_meta($bookingId, '_pwt_payment_method', true)); ?></strong><br>
                                <code><?php echo esc_html((string) get_post_meta($bookingId, '_pwt_payment_reference', true)); ?></code>
                            </td>
                            <td><?php echo esc_html((string) get_post_meta($bookingId, '_pwt_payment_submitted_at', true)); ?></td>
                            <td>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                    <input type="hidden" name="action" value="pwt_review_payment">
                                    <input type="hidden" name="booking_id" value="<?php echo esc_attr((string) $bookingId); ?>">
                                    <?php wp_nonce_field('pwt_review_payment_' . $bookingId); ?>
                                    <button class="button button-primary" name="decision" value="approve"><?php esc_html_e('Verify payment', 'wildtours-plugin'); ?></button>
                                    <button class="button" name="decision" value="reject"><?php esc_html_e('Reject reference', 'wildtours-plugin'); ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function review(): void
    {
        $this->requireAccess();
        $legacyBookingId = absint($_POST['booking_id'] ?? 0);
        $decision = sanitize_key((string) ($_POST['decision'] ?? ''));
        if (!$legacyBookingId || !in_array($decision, ['approve', 'reject'], true)) {
            wp_die(esc_html__('Invalid payment review request.', 'wildtours-plugin'));
        }

        check_admin_referer('pwt_review_payment_' . $legacyBookingId);
        if (get_post_type($legacyBookingId) !== 'pwt_booking') {
            wp_die(esc_html__('Booking not found.', 'wildtours-plugin'));
        }

        if (get_post_meta($legacyBookingId, '_pwt_payment_status', true) !== 'verification_pending') {
            $this->redirect('stale');
        }

        $reference = sanitize_text_field((string) get_post_meta($legacyBookingId, '_pwt_payment_reference', true));
        $method = sanitize_key((string) get_post_meta($legacyBookingId, '_pwt_payment_method', true));
        $amount = (float) get_post_meta($legacyBookingId, '_pwt_payment_submitted_amount', true);
        if ($amount <= 0) {
            $amount = (float) get_post_meta($legacyBookingId, '_pwt_payment_due_amount', true);
        }
        $email = sanitize_email((string) get_post_meta($legacyBookingId, '_pwt_email', true));
        $name = sanitize_text_field((string) get_post_meta($legacyBookingId, '_pwt_name', true));
        $paymentLink = PaymentManager::paymentUrl((string) get_post_meta($legacyBookingId, '_pwt_payment_token', true));
        $normalizedBookingId = absint(get_post_meta($legacyBookingId, '_pwt_normalized_booking_id', true));
        $bookingData = new BookingDataRepository();
        $normalizedBooking = $normalizedBookingId > 0
            ? $bookingData->find($normalizedBookingId)
            : $bookingData->findByLegacyPostId($legacyBookingId);
        $normalizedBookingId = (int) ($normalizedBooking['id'] ?? 0);

        if ($reference === '' || $amount <= 0 || $normalizedBookingId <= 0) {
            wp_die(esc_html__('The payment reference or linked booking data is incomplete. No status was changed.', 'wildtours-plugin'));
        }

        $payments = new PaymentRepository();
        if ($decision === 'reject') {
            $paymentStatus = $payments->totalPaidForBooking($normalizedBookingId) > 0
                ? 'partial_paid'
                : 'pending_payment';
            update_post_meta($legacyBookingId, '_pwt_payment_review_status', 'rejected');
            update_post_meta($legacyBookingId, '_pwt_payment_status', $paymentStatus);
            update_post_meta($legacyBookingId, '_pwt_payment_token_expires', time() + (30 * DAY_IN_SECONDS));
            $this->recordReview($legacyBookingId, $decision, $reference, $method, $amount);
            (new EmailService())->sendPaymentReviewResult([
                'name' => $name,
                'email' => $email,
                'payment_link' => $paymentLink,
            ], false, $amount);
            $this->redirect('rejected');
        }

        $total = (float) get_post_meta($legacyBookingId, '_pwt_payment_total_amount', true);
        $idempotencyKey = 'manual-review-' . $legacyBookingId . '-' . md5($reference);
        $payment = $payments->byIdempotencyKey($idempotencyKey);
        $paidBefore = $payments->totalPaidForBooking($normalizedBookingId);
        if ($payment && ($payment['status'] ?? '') === 'paid') {
            $paidBefore = max(0, $paidBefore - (float) ($payment['amount'] ?? 0));
        }
        $outstandingBefore = max(0, $total - $paidBefore);
        if ($total <= 0 || $amount > $outstandingBefore + 0.005) {
            wp_die(esc_html__('The submitted payment exceeds the remaining booking balance. No status was changed.', 'wildtours-plugin'));
        }

        if (!$payment) {
            $paymentId = $payments->create([
                'booking_id' => $normalizedBookingId,
                'gateway' => 'manual',
                'transaction_id' => $reference,
                'status' => 'paid',
                'amount' => $amount,
                'currency' => (string) ($normalizedBooking['currency'] ?? 'INR'),
                'idempotency_key' => $idempotencyKey,
                'paid_at' => current_time('mysql'),
                'gateway_response' => ['payment_method' => $method, 'reviewed_by' => get_current_user_id()],
            ]);
            if (!$paymentId) {
                wp_die(esc_html__('Unable to record the verified payment. Booking status was not changed.', 'wildtours-plugin'));
            }
        }

        $paidAfter = $paidBefore + $amount;
        $balanceDue = max(0, $total - $paidAfter);
        $paymentStatus = $balanceDue <= 0.005 ? 'paid' : 'partial_paid';
        $bookingStatus = $paymentStatus === 'paid' ? 'paid' : 'confirmed';
        if (!$bookingData->updateStatus($normalizedBookingId, $bookingStatus)) {
            wp_die(esc_html__('The payment was recorded but the normalized booking status could not be updated.', 'wildtours-plugin'));
        }

        update_post_meta($legacyBookingId, '_pwt_payment_review_status', 'approved');
        update_post_meta($legacyBookingId, '_pwt_payment_status', $paymentStatus);
        update_post_meta($legacyBookingId, '_pwt_status', $bookingStatus);
        if ($balanceDue > 0.005) {
            update_post_meta($legacyBookingId, '_pwt_payment_token_expires', time() + (30 * DAY_IN_SECONDS));
        }
        $this->recordReview($legacyBookingId, $decision, $reference, $method, $amount);
        (new EmailService())->sendPaymentReviewResult([
            'name' => $name,
            'email' => $email,
            'payment_link' => $paymentLink,
        ], true, $amount, $balanceDue);
        $this->redirect('approved');
    }

    private function requireAccess(): void
    {
        if (!current_user_can('pwt_manage_payments') && !current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to review payments.', 'wildtours-plugin'));
        }
    }

    private function recordReview(int $bookingId, string $decision, string $reference, string $method, float $amount): void
    {
        $history = get_post_meta($bookingId, '_pwt_payment_review_history', true);
        $history = is_array($history) ? $history : [];
        $history[] = [
            'decision' => $decision,
            'reference' => $reference,
            'method' => $method,
            'amount' => $amount,
            'reviewed_by' => get_current_user_id(),
            'reviewed_at' => current_time('mysql'),
        ];
        update_post_meta($bookingId, '_pwt_payment_review_history', $history);
        update_post_meta($bookingId, '_pwt_payment_reviewed_at', current_time('mysql'));
        update_post_meta($bookingId, '_pwt_payment_reviewed_by', get_current_user_id());
    }

    private function redirect(string $result): never
    {
        wp_safe_redirect(add_query_arg([
            'page' => 'pwt-payments',
            'payment_review' => $result,
        ], admin_url('admin.php')));
        exit;
    }
}
