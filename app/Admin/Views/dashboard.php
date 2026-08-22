<?php

defined('ABSPATH') || exit;

$postTypes = [
	'pwt_package' => __('Packages', 'wildtours-plugin'),
	'pwt_safari' => __('Safaris', 'wildtours-plugin'),
	'pwt_destination' => __('Destinations', 'wildtours-plugin'),
	'pwt_booking' => __('Bookings', 'wildtours-plugin'),
	'pwt_review' => __('Reviews', 'wildtours-plugin'),
];

$counts = [];
foreach ($postTypes as $postType => $label) {
	$obj = wp_count_posts($postType);
	$counts[$postType] = (int) ($obj->publish ?? 0);
}

$bookingCount = (int) get_option('pwt_analytics_booking_count', 0);
$bookingObj = wp_count_posts('pwt_booking');
$totalBookings = (int) ($bookingObj->publish ?? 0);

$statusCounts = [
	'pending_payment' => 0,
	'verification_pending' => 0,
	'partial_paid' => 0,
	'paid' => 0,
	'failed' => 0,
	'cancelled' => 0,
];

$topPackages = [];

global $wpdb;

$statusRows = $wpdb->get_results(
	"SELECT pm.meta_value AS payment_status, COUNT(*) AS total
	FROM {$wpdb->postmeta} pm
	INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
	WHERE p.post_type = 'pwt_booking'
		AND p.post_status = 'publish'
		AND pm.meta_key = '_pwt_payment_status'
	GROUP BY pm.meta_value"
);

if (is_array($statusRows)) {
	foreach ($statusRows as $row) {
		$status = (string) ($row->payment_status ?? '');
		if ($status !== '' && isset($statusCounts[$status])) {
			$statusCounts[$status] = (int) ($row->total ?? 0);
		}
	}
}

// Also query normalized bookings table for comprehensive counting
$normalizedRows = $wpdb->get_results(
	"SELECT status, COUNT(*) AS total
	FROM {$wpdb->prefix}pwt_bookings
	WHERE status IN ('pending_payment', 'verification_pending', 'partial_paid', 'paid', 'failed', 'cancelled')
	GROUP BY status"
);

if (is_array($normalizedRows)) {
	foreach ($normalizedRows as $row) {
		$status = (string) ($row->status ?? '');
		if ($status !== '' && isset($statusCounts[$status])) {
			// Use normalized count if available, otherwise keep legacy count
			if ($statusCounts[$status] === 0) {
				$statusCounts[$status] = (int) ($row->total ?? 0);
			}
		}
	}
}

$topPackageRows = $wpdb->get_results(
	"SELECT pm.meta_value AS package_id, COUNT(*) AS total
	FROM {$wpdb->postmeta} pm
	INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
	WHERE p.post_type = 'pwt_booking'
		AND p.post_status = 'publish'
		AND pm.meta_key = '_pwt_package_id'
	GROUP BY pm.meta_value
	ORDER BY total DESC
	LIMIT 5"
);

if (is_array($topPackageRows)) {
	foreach ($topPackageRows as $row) {
		$packageId = absint((string) ($row->package_id ?? 0));
		if ($packageId > 0) {
			$topPackages[$packageId] = (int) ($row->total ?? 0);
		}
	}
}

$confirmedBookings = $statusCounts['partial_paid'] + $statusCounts['paid'];
$conversionRate = $totalBookings > 0 ? round(($confirmedBookings / $totalBookings) * 100, 2) : 0.0;
?>

<div class="wrap">

<h1>Panna Wild Tour</h1>

<table class="widefat striped">

<tr>

<th>Version</th>

<td><?php echo esc_html(PWT_VERSION); ?></td>

</tr>

<tr>

<th>PHP</th>

<td><?php echo esc_html(PHP_VERSION); ?></td>

</tr>

<tr>

<th>WordPress</th>

<td><?php echo esc_html(get_bloginfo('version')); ?></td>

</tr>

<tr>

<th>Total Tracked Bookings</th>

<td><?php echo esc_html((string) $bookingCount); ?></td>

</tr>

<tr>

<th>Total Booking Records</th>

<td><?php echo esc_html((string) $totalBookings); ?></td>

</tr>

<tr>

<th>Confirmed Bookings</th>

<td><?php echo esc_html((string) $confirmedBookings); ?></td>

</tr>

<tr>

<th>Conversion Rate</th>

<td><?php echo esc_html(number_format_i18n($conversionRate, 2) . '%'); ?></td>

</tr>

</table>

<h2><?php esc_html_e('Content Inventory', 'wildtours-plugin'); ?></h2>

<table class="widefat striped">
	<thead>
		<tr>
			<th><?php esc_html_e('Module', 'wildtours-plugin'); ?></th>
			<th><?php esc_html_e('Published Count', 'wildtours-plugin'); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($postTypes as $postType => $label) : ?>
			<tr>
				<td><?php echo esc_html($label); ?></td>
				<td><?php echo esc_html((string) $counts[$postType]); ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<h2><?php esc_html_e('Payment Funnel', 'wildtours-plugin'); ?></h2>

<table class="widefat striped">
	<thead>
		<tr>
			<th><?php esc_html_e('Status', 'wildtours-plugin'); ?></th>
			<th><?php esc_html_e('Count', 'wildtours-plugin'); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($statusCounts as $status => $count) : ?>
			<tr>
				<td><?php echo esc_html(\PWT\Payments\PaymentManager::statusLabel((string) $status)); ?></td>
				<td><?php echo esc_html((string) $count); ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<h2><?php esc_html_e('Popular Packages by Bookings', 'wildtours-plugin'); ?></h2>

<table class="widefat striped">
	<thead>
		<tr>
			<th><?php esc_html_e('Package', 'wildtours-plugin'); ?></th>
			<th><?php esc_html_e('Bookings', 'wildtours-plugin'); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php if (!empty($topPackages)) : ?>
			<?php foreach ($topPackages as $packageId => $count) : ?>
				<tr>
					<td><?php echo esc_html(get_the_title((int) $packageId) ?: __('(untitled)', 'wildtours-plugin')); ?></td>
					<td><?php echo esc_html((string) $count); ?></td>
				</tr>
			<?php endforeach; ?>
		<?php else : ?>
			<tr>
				<td colspan="2"><?php esc_html_e('No booking data available yet.', 'wildtours-plugin'); ?></td>
			</tr>
		<?php endif; ?>
	</tbody>
</table>

</div>