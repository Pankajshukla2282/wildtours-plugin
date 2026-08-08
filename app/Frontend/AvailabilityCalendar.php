<?php

namespace PWT\Frontend;

defined('ABSPATH') || exit;

class AvailabilityCalendar
{
    public function register(): void
    {
        add_shortcode('pwt_availability_calendar', [$this, 'shortcode']);
    }

    public function shortcode(): string
    {
        $packages = get_posts([
            'post_type' => 'pwt_package',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        $selectedPackage = absint($_GET['availability_package'] ?? 0);
        $selectedDate = sanitize_text_field($_GET['availability_date'] ?? '');
        $isAvailable = null;

        if ($selectedPackage && $selectedDate) {
            $isAvailable = self::isDateAvailable($selectedPackage, $selectedDate);
        }

        ob_start();
        ?>
        <section class="pwt-section">
            <header class="pwt-section-header">
                <h2><?php esc_html_e('Check Availability', 'wildtours-plugin'); ?></h2>
                <p><?php esc_html_e('Check if your preferred package is open on a selected date.', 'wildtours-plugin'); ?></p>
            </header>
            <form method="get" class="pwt-form-grid">
                <label>
                    <span><?php esc_html_e('Package', 'wildtours-plugin'); ?></span>
                    <select name="availability_package" required>
                        <option value=""><?php esc_html_e('Select package', 'wildtours-plugin'); ?></option>
                        <?php foreach ($packages as $package) : ?>
                            <option value="<?php echo esc_attr((string) $package->ID); ?>" <?php selected($selectedPackage, (int) $package->ID); ?>><?php echo esc_html($package->post_title); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span><?php esc_html_e('Date', 'wildtours-plugin'); ?></span>
                    <input type="date" name="availability_date" min="<?php echo esc_attr(current_time('Y-m-d')); ?>" value="<?php echo esc_attr($selectedDate); ?>" required>
                </label>
                <p><button type="submit" class="pwt-btn"><?php esc_html_e('Check', 'wildtours-plugin'); ?></button></p>
            </form>
            <?php if ($isAvailable !== null) : ?>
                <p class="pwt-form-message <?php echo $isAvailable ? 'is-success' : 'is-error'; ?>">
                    <?php echo esc_html($isAvailable ? __('Date is available.', 'wildtours-plugin') : __('Date is unavailable. Please choose another date.', 'wildtours-plugin')); ?>
                </p>
            <?php endif; ?>
        </section>
        <?php

        return ob_get_clean();
    }

    public static function isDateAvailable(int $packageId, string $date): bool
    {
        $today = current_time('Y-m-d');

        if ($date < $today) {
            return false;
        }

        $settings = get_option('pwt_settings', []);
        $blockedDates = array_filter(array_map('trim', explode(',', (string) ($settings['blocked_dates'] ?? ''))));

        if (in_array($date, $blockedDates, true)) {
            return false;
        }

        $limit = max(1, absint($settings['daily_booking_limit'] ?? 6));

        $bookings = get_posts([
            'post_type' => 'pwt_booking',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'fields' => 'ids',
            'no_found_rows' => true,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_pwt_travel_date',
                    'value' => $date,
                ],
                [
                    'key' => '_pwt_package_id',
                    'value' => $packageId,
                ],
            ],
        ]);

        return count($bookings) < $limit;
    }
}
