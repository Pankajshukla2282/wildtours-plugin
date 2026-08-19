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
                <p><?php esc_html_e('Pick a package, then click any available date to confirm it.', 'wildtours-plugin'); ?></p>
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
                    <input type="date" name="availability_date" min="<?php echo esc_attr(current_time('Y-m-d')); ?>" value="<?php echo esc_attr($selectedDate); ?>">
                </label>
                <p><button type="submit" class="pwt-btn"><?php esc_html_e('Check', 'wildtours-plugin'); ?></button></p>
            </form>
            <?php if ($isAvailable !== null) : ?>
                <p class="pwt-form-message <?php echo $isAvailable ? 'is-success' : 'is-error'; ?>">
                    <?php echo esc_html($isAvailable ? __('Date is available.', 'wildtours-plugin') : __('Date is unavailable. Please choose another date.', 'wildtours-plugin')); ?>
                </p>
            <?php endif; ?>
            <?php if ($selectedPackage) : ?>
                <?php echo $this->renderCalendar($selectedPackage, $selectedDate); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php else : ?>
                <p class="pwt-calendar-hint"><?php esc_html_e('Select a package to see its available dates.', 'wildtours-plugin'); ?></p>
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

    /**
     * Render a current + next month calendar grid. Available days are links
     * that run the availability check for that date; blocked, past and
     * sold-out days are rendered as non-clickable cells.
     */
    private function renderCalendar(int $packageId, string $selectedDate): string
    {
        $today = current_time('Y-m-d');
        $settings = get_option('pwt_settings', []);
        $blockedDates = array_filter(array_map('trim', explode(',', (string) ($settings['blocked_dates'] ?? ''))));
        $limit = max(1, absint($settings['daily_booking_limit'] ?? 6));

        $html = '<div class="pwt-calendar">';

        for ($offset = 0; $offset < 2; $offset++) {
            $html .= $this->renderMonth($packageId, $offset, $today, $selectedDate, $blockedDates, $limit);
        }

        $html .= '<p class="pwt-calendar-legend">'
            . '<span class="pwt-calendar-day is-available" aria-hidden="true">21</span>'
            . esc_html__(' Available', 'wildtours-plugin')
            . '<span class="pwt-calendar-day is-unavailable" aria-hidden="true">22</span>'
            . esc_html__(' Unavailable / sold out', 'wildtours-plugin')
            . '</p>';

        $html .= '</div>';

        return $html;
    }

    /**
     * @param string[] $blockedDates
     */
    private function renderMonth(int $packageId, int $offset, string $today, string $selectedDate, array $blockedDates, int $limit): string
    {
        $baseTs = strtotime(date('Y-m-01', current_time('timestamp')) . ' +' . $offset . ' months');

        if ($baseTs === false) {
            return '';
        }

        $year = (int) date('Y', $baseTs);
        $month = (int) date('n', $baseTs);
        $daysInMonth = (int) date('t', $baseTs);
        $firstDow = (int) date('w', $baseTs);
        $monthLabel = date_i18n('F Y', $baseTs);

        $counts = $this->bookingCountsByDate($packageId, $year, $month);

        $html = '<div class="pwt-calendar-month">';
        $html .= '<h3 class="pwt-calendar-title">' . esc_html($monthLabel) . '</h3>';
        $html .= '<div class="pwt-calendar-grid">';

        foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dow) {
            $html .= '<span class="pwt-calendar-dow">' . esc_html__($dow, 'wildtours-plugin') . '</span>';
        }

        for ($i = 0; $i < $firstDow; $i++) {
            $html .= '<span class="pwt-calendar-blank"></span>';
        }

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $isPast = $date < $today;
            $isBlocked = in_array($date, $blockedDates, true);
            $isSoldOut = ($counts[$date] ?? 0) >= $limit;
            $isAvailable = !$isPast && !$isBlocked && !$isSoldOut;

            $classes = ['pwt-calendar-day'];

            if ($isAvailable) {
                $classes[] = 'is-available';
            } elseif ($isSoldOut) {
                $classes[] = 'is-unavailable';
            } elseif ($isBlocked) {
                $classes[] = 'is-blocked';
            } else {
                $classes[] = 'is-past';
            }

            if ($date === $today) {
                $classes[] = 'is-today';
            }

            if ($date === $selectedDate) {
                $classes[] = 'is-selected';
            }

            if ($isAvailable) {
                $url = add_query_arg([
                    'availability_package' => $packageId,
                    'availability_date' => $date,
                ]);
                $html .= '<a class="' . esc_attr(implode(' ', $classes)) . '" href="' . esc_url($url) . '" aria-label="' . esc_attr(sprintf(__('Check availability on %s', 'wildtours-plugin'), date_i18n('j F Y', strtotime($date)))) . '">' . $day . '</a>';
            } else {
                $title = $isSoldOut
                    ? __('Sold out', 'wildtours-plugin')
                    : ($isBlocked ? __('Not available on this date', 'wildtours-plugin') : __('Past date', 'wildtours-plugin'));
                $html .= '<span class="' . esc_attr(implode(' ', $classes)) . '" title="' . esc_attr($title) . '">' . $day . '</span>';
            }
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Booking counts per travel date for one package and calendar month.
     *
     * @return array<string, int>
     */
    private function bookingCountsByDate(int $packageId, int $year, int $month): array
    {
        $counts = [];

        $bookings = get_posts([
            'post_type' => 'pwt_booking',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_pwt_package_id',
                    'value' => $packageId,
                ],
                [
                    'key' => '_pwt_travel_date',
                    'value' => sprintf('%04d-%02d-01', $year, $month),
                    'compare' => '>=',
                    'type' => 'DATE',
                ],
                [
                    'key' => '_pwt_travel_date',
                    'value' => sprintf('%04d-%02d-%02d', $year, $month, (int) date('t', strtotime(sprintf('%04d-%02d-01', $year, $month)))),
                    'compare' => '<=',
                    'type' => 'DATE',
                ],
            ],
        ]);

        foreach ($bookings as $bookingId) {
            $travelDate = (string) get_post_meta((int) $bookingId, '_pwt_travel_date', true);

            if ($travelDate !== '') {
                $counts[$travelDate] = ($counts[$travelDate] ?? 0) + 1;
            }
        }

        return $counts;
    }
}