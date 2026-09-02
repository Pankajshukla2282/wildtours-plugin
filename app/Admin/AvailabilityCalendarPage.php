<?php

declare(strict_types=1);

namespace PWT\Admin;

defined('ABSPATH') || exit;

use PWT\Availability\AvailabilityRepository;
use PWT\Core\Database\Schema;

final class AvailabilityCalendarPage
{
    public function __construct()
    {
    }

    public function register(): void
    {
        // Note: Availability Calendar is already provided by OperationsDashboard
        // at admin.php?page=pwt-availability. This class is kept for backward
        // compatibility but does not register a new submenu page.
    }

    public function enqueueAssets(
        string $hook
    ): void {
        if (
            strpos(
                $hook,
                'pwt-availability-calendar'
            ) === false
        ) {
            return;
        }

        $file = defined('PWT_PLUGIN_DIR')
            ? PWT_PLUGIN_DIR . 'assets/css/admin-availability-calendar.css'
            : '';

        if ($file !== '' && file_exists($file)) {
            wp_enqueue_style(
                'pwt-availability-calendar',
                PWT_PLUGIN_URL . 'assets/css/admin-availability-calendar.css',
                [],
                defined('PWT_VERSION')
                    ? PWT_VERSION
                    : null
            );
        }
    }

    public function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__(
                    'You do not have permission to access this page.',
                    'wildtours-plugin'
                )
            );
        }

        $view = isset($_GET['view'])
            ? sanitize_key(
                wp_unslash($_GET['view'])
            )
            : 'month';

        if ($view === 'day') {
            $this->renderDayView();
            return;
        }

        $year = isset($_GET['year'])
            ? max(
                2020,
                absint($_GET['year'])
            )
            : (int) wp_date('Y');

        $month = isset($_GET['month'])
            ? min(
                12,
                max(
                    1,
                    absint($_GET['month'])
                )
            )
            : (int) wp_date('n');

        $resourceType = isset(
            $_GET['resource_type']
        )
            ? sanitize_key(
                wp_unslash(
                    $_GET['resource_type']
                )
            )
            : '';

        $resourceId = isset(
            $_GET['resource_id']
        )
            ? absint(
                $_GET['resource_id']
            )
            : 0;

        $resources = $this->getResources(
            $resourceType
        );

        $firstDay = sprintf(
            '%04d-%02d-01',
            $year,
            $month
        );

        $lastDay = wp_date(
            'Y-m-t',
            strtotime($firstDay)
        );

        $calendar = [];

        if (
            $resourceId > 0
            && $resourceType !== ''
        ) {
            $calendar = $this->getCalendar(
                $resourceId,
                $resourceType,
                $firstDay,
                $lastDay
            );
        }

        ?>
        <div class="wrap pwt-availability-calendar">

            <h1>
                <?php
                esc_html_e(
                    'Availability Calendar',
                    'wildtours-plugin'
                );
                ?>
            </h1>

            <?php
            $this->renderFilters(
                $year,
                $month,
                $resourceType,
                $resourceId,
                $resources
            );
            ?>

            <?php if (
                !$resourceType
                || !$resourceId
            ) : ?>

                <div class="notice notice-info inline">
                    <p>
                        <?php
                        esc_html_e(
                            'Select a resource type and resource to view availability.',
                            'wildtours-plugin'
                        );
                        ?>
                    </p>
                </div>

            <?php else : ?>

                <?php
                $this->renderCalendar(
                    $year,
                    $month,
                    $resourceType,
                    $resourceId,
                    $calendar
                );
                ?>

            <?php endif; ?>

        </div>
        <?php
    }

    private function renderFilters(
        int $year,
        int $month,
        string $resourceType,
        int $resourceId,
        array $resources
    ): void {
        ?>
        <form
            method="get"
            class="pwt-calendar-filters"
        >
            <input
                type="hidden"
                name="page"
                value="pwt-availability-calendar"
            >

            <label>
                <span>
                    <?php esc_html_e(
                        'Year',
                        'wildtours-plugin'
                    ); ?>
                </span>

                <select name="year">
                    <?php for (
                        $y = (int) wp_date('Y') - 2;
                        $y <= (int) wp_date('Y') + 3;
                        $y++
                    ) : ?>

                        <option
                            value="<?php echo esc_attr($y); ?>"
                            <?php selected($year, $y); ?>
                        >
                            <?php echo esc_html($y); ?>
                        </option>

                    <?php endfor; ?>
                </select>
            </label>

            <label>
                <span>
                    <?php esc_html_e(
                        'Month',
                        'wildtours-plugin'
                    ); ?>
                </span>

                <select name="month">

                    <?php for (
                        $m = 1;
                        $m <= 12;
                        $m++
                    ) : ?>

                        <option
                            value="<?php echo esc_attr($m); ?>"
                            <?php selected($month, $m); ?>
                        >
                            <?php
                            echo esc_html(
                                wp_date(
                                    'F',
                                    mktime(
                                        0,
                                        0,
                                        0,
                                        $m,
                                        1,
                                        $year
                                    )
                                )
                            );
                            ?>
                        </option>

                    <?php endfor; ?>

                </select>
            </label>

            <label>
                <span>
                    <?php esc_html_e(
                        'Resource Type',
                        'wildtours-plugin'
                    ); ?>
                </span>

                <select name="resource_type">

                    <option value="">
                        <?php esc_html_e(
                            'Select type',
                            'wildtours-plugin'
                        ); ?>
                    </option>

                    <?php foreach (
                        $this->getResourceTypes()
                        as $type => $label
                    ) : ?>

                        <option
                            value="<?php echo esc_attr($type); ?>"
                            <?php selected(
                                $resourceType,
                                $type
                            ); ?>
                        >
                            <?php echo esc_html($label); ?>
                        </option>

                    <?php endforeach; ?>

                </select>
            </label>

            <label>
                <span>
                    <?php esc_html_e(
                        'Resource',
                        'wildtours-plugin'
                    ); ?>
                </span>

                <select name="resource_id">

                    <option value="0">
                        <?php esc_html_e(
                            'Select resource',
                            'wildtours-plugin'
                        ); ?>
                    </option>

                    <?php foreach (
                        $resources
                        as $resource
                    ) : ?>

                        <option
                            value="<?php
                            echo esc_attr(
                                $resource['id']
                            );
                            ?>"
                            <?php selected(
                                $resourceId,
                                $resource['id']
                            ); ?>
                        >
                            <?php
                            echo esc_html(
                                $resource['title']
                            );
                            ?>
                        </option>

                    <?php endforeach; ?>

                </select>
            </label>

            <?php
            submit_button(
                __('View Calendar', 'wildtours-plugin'),
                'primary',
                'submit',
                false
            );
            ?>

        </form>
        <?php
    }

    private function renderCalendar(
        int $year,
        int $month,
        string $resourceType,
        int $resourceId,
        array $calendar
    ): void {
        $firstTimestamp = strtotime(
            sprintf(
                '%04d-%02d-01',
                $year,
                $month
            )
        );

        $daysInMonth = (int) wp_date(
            't',
            $firstTimestamp
        );

        $startWeekday = (int) wp_date(
            'w',
            $firstTimestamp
        );

        ?>
        <div class="pwt-calendar-header">

            <h2>
                <?php
                echo esc_html(
                    wp_date(
                        'F Y',
                        $firstTimestamp
                    )
                );
                ?>
            </h2>

        </div>

        <div class="pwt-calendar-grid">

            <?php foreach (
                [
                    __('Sun', 'wildtours-plugin'),
                    __('Mon', 'wildtours-plugin'),
                    __('Tue', 'wildtours-plugin'),
                    __('Wed', 'wildtours-plugin'),
                    __('Thu', 'wildtours-plugin'),
                    __('Fri', 'wildtours-plugin'),
                    __('Sat', 'wildtours-plugin'),
                ]
                as $weekday
            ) : ?>

                <div class="pwt-calendar-weekday">
                    <?php echo esc_html($weekday); ?>
                </div>

            <?php endforeach; ?>

            <?php for (
                $i = 0;
                $i < $startWeekday;
                $i++
            ) : ?>

                <div class="pwt-calendar-empty"></div>

            <?php endfor; ?>

            <?php for (
                $day = 1;
                $day <= $daysInMonth;
                $day++
            ) :

                $date = sprintf(
                    '%04d-%02d-%02d',
                    $year,
                    $month,
                    $day
                );

                $data = $calendar[$date]
                    ?? [
                        'capacity' => 0,
                        'reserved' => 0,
                        'blocked' => 0,
                        'held' => 0,
                        'remaining' => 0,
                    ];

                $class = $this->availabilityClass(
                    $data
                );

                $detailUrl = add_query_arg(
                    [
                        'page' => 'pwt-availability-calendar',
                        'view' => 'day',
                        'resource_type' => $resourceType,
                        'resource_id' => $resourceId,
                        'date' => $date,
                    ],
                    admin_url('admin.php')
                );
                ?>

                <a
                    class="pwt-calendar-day <?php echo esc_attr($class); ?>"
                    href="<?php echo esc_url($detailUrl); ?>"
                >

                    <span class="pwt-calendar-date">
                        <?php echo esc_html($day); ?>
                    </span>

                    <strong class="pwt-calendar-remaining">
                        <?php
                        printf(
                            esc_html__(
                                '%d left',
                                'wildtours-plugin'
                            ),
                            (int) $data['remaining']
                        );
                        ?>
                    </strong>

                    <span class="pwt-calendar-meta">
                        <?php
                        printf(
                            esc_html__(
                                'Capacity: %d',
                                'wildtours-plugin'
                            ),
                            (int) $data['capacity']
                        );
                        ?>
                    </span>

                    <span class="pwt-calendar-meta">
                        <?php
                        printf(
                            esc_html__(
                                'Reserved: %d',
                                'wildtours-plugin'
                            ),
                            (int) $data['reserved']
                        );
                        ?>
                    </span>

                    <span class="pwt-calendar-meta">
                        <?php
                        printf(
                            esc_html__(
                                'Held: %d',
                                'wildtours-plugin'
                            ),
                            (int) $data['held']
                        );
                        ?>
                    </span>

                </a>

            <?php endfor; ?>

        </div>
        <?php
    }

    private function renderDayView(): void
    {
        $resourceType = isset(
            $_GET['resource_type']
        )
            ? sanitize_key(
                wp_unslash(
                    $_GET['resource_type']
                )
            )
            : '';

        $resourceId = isset(
            $_GET['resource_id']
        )
            ? absint(
                $_GET['resource_id']
            )
            : 0;

        $date = isset($_GET['date'])
            ? sanitize_text_field(
                wp_unslash($_GET['date'])
            )
            : '';

        if (
            !$resourceType
            || !$resourceId
            || !$date
        ) {
            wp_die(
                esc_html__(
                    'Invalid availability request.',
                    'wildtours-plugin'
                )
            );
        }

        $availability = $this->availability->check(
            $resourceId,
            $resourceType,
            $date
        );

        $backUrl = add_query_arg(
            [
                'page' => 'pwt-availability-calendar',
                'year' => wp_date(
                    'Y',
                    strtotime($date)
                ),
                'month' => wp_date(
                    'n',
                    strtotime($date)
                ),
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
            ],
            admin_url('admin.php')
        );

        ?>
        <div class="wrap">

            <h1>
                <?php
                echo esc_html(
                    sprintf(
                        __('Availability: %s', 'wildtours-plugin'),
                        $date
                    )
                );
                ?>
            </h1>

            <p>
                <a
                    class="button"
                    href="<?php echo esc_url($backUrl); ?>"
                >
                    <?php
                    esc_html_e(
                        '← Back to Calendar',
                        'wildtours-plugin'
                    );
                    ?>
                </a>
            </p>

            <table class="widefat striped">
                <tbody>

                    <?php foreach (
                        [
                            'capacity' => __('Capacity', 'wildtours-plugin'),
                            'reserved' => __('Reserved', 'wildtours-plugin'),
                            'held' => __('Active Holds', 'wildtours-plugin'),
                            'blocked' => __('Blocked', 'wildtours-plugin'),
                            'remaining' => __('Remaining', 'wildtours-plugin'),
                        ]
                        as $key => $label
                    ) : ?>

                        <tr>
                            <th>
                                <?php echo esc_html($label); ?>
                            </th>

                            <td>
                                <?php
                                echo esc_html(
                                    (string) (
                                        $availability[$key]
                                        ?? 0
                                    )
                                );
                                ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                </tbody>
            </table>

            <?php
            $this->renderRelatedBookings(
                $resourceId,
                $resourceType,
                $date
            );
            ?>

        </div>
        <?php
    }

    private function renderRelatedBookings(
        int $resourceId,
        string $resourceType,
        string $date
    ): void {
        global $wpdb;

        $table = Schema::tables()['items'];

        /*
         * Same-day services:
         * start_date = selected date
         *
         * Multi-day services:
         * start_date <= selected date
         * end_date > selected date
         *
         * The second condition prevents an exclusive checkout
         * date from consuming inventory.
         */
        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                FROM {$table}
                WHERE object_id = %d
                  AND item_type = %s
                  AND (
                      start_date = %s
                      OR (
                          start_date <= %s
                          AND end_date > %s
                      )
                  )
                ORDER BY booking_id DESC",
                $resourceId,
                $resourceType,
                $date,
                $date,
                $date
            ),
            ARRAY_A
        ) ?: [];

        ?>
        <h2>
            <?php
            esc_html_e(
                'Related Bookings',
                'wildtours-plugin'
            );
            ?>
        </h2>

        <?php if (!$items) : ?>

            <p>
                <?php
                esc_html_e(
                    'No booking items were found for this date.',
                    'wildtours-plugin'
                );
                ?>
            </p>

            <?php
            return;
        endif;
        ?>

        <table class="widefat striped">

            <thead>
                <tr>
                    <th>
                        <?php esc_html_e(
                            'Booking',
                            'wildtours-plugin'
                        ); ?>
                    </th>

                    <th>
                        <?php esc_html_e(
                            'Service',
                            'wildtours-plugin'
                        ); ?>
                    </th>

                    <th>
                        <?php esc_html_e(
                            'Quantity',
                            'wildtours-plugin'
                        ); ?>
                    </th>

                    <th>
                        <?php esc_html_e(
                            'Dates',
                            'wildtours-plugin'
                        ); ?>
                    </th>
                </tr>
            </thead>

            <tbody>

                <?php foreach (
                    $items
                    as $item
                ) :

                    $bookingId = absint(
                        $item['booking_id']
                    );

                    $bookingUrl = add_query_arg(
                        [
                            'page' => 'pwt-booking-detail',
                            'booking_id' => $bookingId,
                        ],
                        admin_url('admin.php')
                    );
                    ?>

                    <tr>

                        <td>
                            <a
                                href="<?php
                                echo esc_url(
                                    $bookingUrl
                                );
                                ?>"
                            >
                                <?php
                                printf(
                                    esc_html__(
                                        'Booking #%d',
                                        'wildtours-plugin'
                                    ),
                                    $bookingId
                                );
                                ?>
                            </a>
                        </td>

                        <td>
                            <?php
                            echo esc_html(
                                $item['name']
                                ?: $item['item_type']
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo esc_html(
                                (string) (
                                    $item['quantity']
                                    ?? 1
                                )
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo esc_html(
                                (string) (
                                    $item['start_date']
                                    ?? ''
                                )
                            );
                            ?>

                            →

                            <?php
                            echo esc_html(
                                (string) (
                                    $item['end_date']
                                    ?? ''
                                )
                            );
                            ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>
        <?php
    }

    private function getCalendar(
        int $resourceId,
        string $resourceType,
        string $startDate,
        string $endDate
    ): array {
        $calendar = [];

        try {
            $start = new \DateTimeImmutable(
                $startDate
            );

            $end = new \DateTimeImmutable(
                $endDate
            );
        } catch (\Throwable) {
            return [];
        }

        for (
            $date = $start;
            $date <= $end;
            $date = $date->modify('+1 day')
        ) {
            $dateString = $date->format(
                'Y-m-d'
            );

            $calendar[$dateString] =
                $this->availability->check(
                    $resourceId,
                    $resourceType,
                    $dateString
                );
        }

        return $calendar;
    }

    private function availabilityClass(
        array $data
    ): string {
        $remaining = (int) (
            $data['remaining'] ?? 0
        );

        $capacity = (int) (
            $data['capacity'] ?? 0
        );

        if ($remaining <= 0) {
            return 'pwt-sold-out';
        }

        if (
            $capacity > 0
            && ($remaining / $capacity) <= 0.25
        ) {
            return 'pwt-warning';
        }

        return 'pwt-open';
    }

    private function getResourceTypes(): array
    {
        return apply_filters(
            'pwt_availability_resource_types',
            [
                'resort' => __(
                    'Resorts',
                    'wildtours-plugin'
                ),
                'safari' => __(
                    'Safaris',
                    'wildtours-plugin'
                ),
                'vehicle' => __(
                    'Vehicles',
                    'wildtours-plugin'
                ),
                'guide' => __(
                    'Guides',
                    'wildtours-plugin'
                ),
                'transfer' => __(
                    'Transfers',
                    'wildtours-plugin'
                ),
            ]
        );
    }

    private function getResources(
        string $resourceType
    ): array {
        if ($resourceType === '') {
            return [];
        }

        $postType = apply_filters(
            'pwt_availability_resource_post_type',
            $resourceType,
            $resourceType
        );

        if (!post_type_exists($postType)) {
            return [];
        }

        $posts = get_posts(
            [
                'post_type' => $postType,
                'post_status' => [
                    'publish',
                    'private',
                    'draft',
                ],
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
            ]
        );

        return array_map(
            static function (
                \WP_Post $post
            ): array {
                return [
                    'id' => (int) $post->ID,
                    'title' => $post->post_title,
                ];
            },
            $posts
        );
    }
}