<?php

namespace PWT\Bookings;

defined('ABSPATH') || exit;

class BookingForm
{
	public function render(): string
	{
		$currentPostId = get_queried_object_id();
		$defaultPackageId = is_singular('pwt_package') ? (int) $currentPostId : 0;

		$packages = get_posts([
			'post_type' => 'pwt_package',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'orderby' => 'title',
			'order' => 'ASC',
		]);

		ob_start();
		?>
		<section class="pwt-section" id="pwt-booking">
			<header class="pwt-section-header">
				<h2><?php esc_html_e('Book Your Wild Tour', 'wildtours-plugin'); ?></h2>
				<p><?php esc_html_e('Share your travel date and preferences. We will confirm availability and pricing.', 'wildtours-plugin'); ?></p>
			</header>

			<form class="pwt-booking-form" method="post">
				<input type="hidden" name="action" value="pwt_booking">
				<input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('pwt_booking')); ?>">

				<div class="pwt-form-grid">
					<label>
						<span><?php esc_html_e('Full Name *', 'wildtours-plugin'); ?></span>
						<input type="text" name="name" required>
					</label>

					<label>
						<span><?php esc_html_e('Phone *', 'wildtours-plugin'); ?></span>
						<input type="text" name="phone" required>
					</label>

					<label>
						<span><?php esc_html_e('Email', 'wildtours-plugin'); ?></span>
						<input type="email" name="email">
					</label>

					<label>
						<span><?php esc_html_e('Travel Date *', 'wildtours-plugin'); ?></span>
						<input type="date" name="travel_date" min="<?php echo esc_attr(current_time('Y-m-d')); ?>" required>
					</label>

					<label>
						<span><?php esc_html_e('Number of Persons *', 'wildtours-plugin'); ?></span>
						<input type="number" name="persons" min="1" max="30" required>
					</label>

					<label>
						<span><?php esc_html_e('Preferred Package', 'wildtours-plugin'); ?></span>
						<select name="package_id">
							<option value=""><?php esc_html_e('Select package', 'wildtours-plugin'); ?></option>
							<?php foreach ($packages as $package) : ?>
								<option value="<?php echo esc_attr((string) $package->ID); ?>" <?php selected($defaultPackageId, (int) $package->ID); ?>><?php echo esc_html($package->post_title); ?></option>
							<?php endforeach; ?>
						</select>
					</label>

					<label class="pwt-full-width">
						<span><?php esc_html_e('Message', 'wildtours-plugin'); ?></span>
						<textarea name="message" rows="4" placeholder="<?php esc_attr_e('Tell us your travel goals, stay type, and any special requests.', 'wildtours-plugin'); ?>"></textarea>
					</label>
				</div>

				<button type="submit" class="pwt-btn"><?php esc_html_e('Send Booking Request', 'wildtours-plugin'); ?></button>
				<p class="pwt-estimate" aria-live="polite"></p>
				<p class="pwt-form-message" aria-live="polite"></p>
			</form>
		</section>
		<?php

		return ob_get_clean();
	}
}
