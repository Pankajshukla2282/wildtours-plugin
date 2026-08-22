<?php

declare(strict_types=1);

namespace PWT\Admin;

defined('ABSPATH') || exit;

class Settings
{
    public function register(): void
    {
        add_action(
            'admin_init',
            [$this, 'registerSettings']
        );
    }

    /**
     * Register all three settings sections.
     */
    public function registerSettings(): void
    {
        // --- General Settings ---
        $this->registerGeneralSettings();

        // --- Payments Settings ---
        $this->registerPaymentsSettings();

        // --- Availability Settings ---
        $this->registerAvailabilitySettings();
    }

    /**
     * Register General Settings section.
     */
    private function registerGeneralSettings(): void
    {
        register_setting(
            'pwt_settings_general',
            'pwt_settings_general',
            [
                'sanitize_callback' => [$this, 'sanitizeGeneral'],
            ]
        );

        add_settings_section(
            'pwt_general',
            __('General Settings', 'wildtours-plugin'),
            '__return_false',
            'pwt-settings-general'
        );

        $this->addGeneralField('company_name', __('Company Name'), __('Panna Wild Tour'));
        $this->addGeneralField('contact_phone', __('Contact Phone'), __('+91 90000 00000'));
        $this->addGeneralField('contact_email', __('Contact Email'), __('hello@example.com'));
        $this->addGeneralField('whatsapp_number', __('WhatsApp Number'), __('919000000000'));
        $this->addGeneralField('company_address', __('Company Address'), __('Panna, Madhya Pradesh, India'));
        $this->addGeneralField('booking_email', __('Booking Notification Email'), __('bookings@example.com'));
        $this->addGeneralField('hero_title', __('Homepage Hero Title'), __('Explore Panna Tiger Reserve'));
        $this->addGeneralField('hero_subtitle', __('Homepage Hero Subtitle'), __('Trusted safari planning, premium stays, and complete travel support.'));
        $this->addGeneralField('featured_package_ids', __('Featured Package IDs (comma separated)'), __('660,668,675'));
        $this->addGeneralField('google_analytics_id', __('Google Analytics ID'), __('G-XXXXXXXXXX'));
    }

    /**
     * Register Payments Settings section.
     */
    private function registerPaymentsSettings(): void
    {
        register_setting(
            'pwt_settings_payments',
            'pwt_settings_payments',
            [
                'sanitize_callback' => [$this, 'sanitizePayments'],
            ]
        );

        add_settings_section(
            'pwt_payments',
            __('Payment Settings', 'wildtours-plugin'),
            '__return_false',
            'pwt-settings-payments'
        );

        $this->addPaymentsField('payment_page_url', __('Payment Page URL'), __('https://example.com/payment/'));
        $this->addPaymentsField('payment_upi_id', __('UPI ID'), __('business@upi'));
        $this->addPaymentsField('payment_gateway', __('Payment Gateway Mode'), [
            'manual' => __('Manual Reference (UPI/Bank)'),
            'razorpay' => __('Razorpay (Hosted Redirect)'),
            'cashfree' => __('Cashfree (Hosted Redirect)'),
        ]);
        $this->addPaymentsField('payment_gateway_checkout_url', __('Gateway Checkout URL'), __('https://payments.example.com/checkout'));
        $this->addPaymentsField('payment_methods', __('Allowed Payment Methods (comma separated)'), __('upi,bank_transfer,cash'));
        $this->addPaymentsField('payment_advance_percent', __('Advance Payment Percent'), __('30'));
        $this->addPaymentsField('payment_instructions', __('Payment Instructions'), __('Share bank or UPI instructions for advance booking confirmation.'));
    }

    /**
     * Register Availability Settings section.
     */
    private function registerAvailabilitySettings(): void
    {
        register_setting(
            'pwt_settings_availability',
            'pwt_settings_availability',
            [
                'sanitize_callback' => [$this, 'sanitizeAvailability'],
            ]
        );

        add_settings_section(
            'pwt_availability',
            __('Availability Settings', 'wildtours-plugin'),
            '__return_false',
            'pwt-settings-availability'
        );

        $this->addAvailabilityField('blocked_dates', __('Blocked Dates (comma separated YYYY-MM-DD)'), __('2026-12-25,2026-12-31'));
        $this->addAvailabilityField('daily_booking_limit', __('Daily Booking Limit per Package'), __('6'));
        $this->addAvailabilityField('rest_api_key', __('REST Booking API Key'), __('Set a long random key for X-PWT-API-KEY header'));
        $this->addAvailabilityField('rest_rate_limit_per_minute', __('REST Rate Limit Per Minute'), __('20'));
    }

    /**
     * Add a field to the General section.
     */
    private function addGeneralField(string $id, string $label, string $placeholder): void
    {
        add_settings_field(
            $id,
            $label,
            function () use ($id, $placeholder) {
                $options = get_option('pwt_settings_general', []);
                $value = $options[$id] ?? '';
                ?>
                <input
                    type="text"
                    class="regular-text"
                    name="pwt_settings_general[<?php echo esc_attr($id); ?>]"
                    placeholder="<?php echo esc_attr($placeholder); ?>"
                    value="<?php echo esc_attr($value); ?>">
                <?php
            },
            'pwt-settings-general',
            $id
        );
    }

    /**
     * Add a field to the Payments section.
     */
    private function addPaymentsField(string $id, string $label, string|array $placeholderOrOptions): void
    {
        add_settings_field(
            $id,
            $label,
            function () use ($id, $placeholderOrOptions) {
                $options = get_option('pwt_settings_payments', []);
                $value = $options[$id] ?? '';

                if (is_array($placeholderOrOptions)) {
                    ?>
                    <select class="regular-text" name="pwt_settings_payments[<?php echo esc_attr($id); ?>]">
                        <?php foreach ($placeholderOrOptions as $choiceValue => $choiceLabel) : ?>
                            <option value="<?php echo esc_attr((string) $choiceValue); ?>" <?php selected($value, (string) $choiceValue); ?>>
                                <?php echo esc_html((string) $choiceLabel); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php
                } else {
                    ?>
                    <input
                        type="text"
                        class="regular-text"
                        name="pwt_settings_payments[<?php echo esc_attr($id); ?>]"
                        placeholder="<?php echo esc_attr($placeholderOrOptions); ?>"
                        value="<?php echo esc_attr($value); ?>">
                    <?php
                }
            },
            'pwt-settings-payments',
            $id
        );
    }

    /**
     * Add a field to the Availability section.
     */
    private function addAvailabilityField(string $id, string $label, string $placeholder): void
    {
        add_settings_field(
            $id,
            $label,
            function () use ($id, $placeholder) {
                $options = get_option('pwt_settings_availability', []);
                $value = $options[$id] ?? '';
                ?>
                <input
                    type="text"
                    class="regular-text"
                    name="pwt_settings_availability[<?php echo esc_attr($id); ?>]"
                    placeholder="<?php echo esc_attr($placeholder); ?>"
                    value="<?php echo esc_attr($value); ?>">
                <?php
            },
            'pwt-settings-availability',
            $id
        );
    }

    /**
     * Sanitize General settings.
     */
    public function sanitizeGeneral(array $input): array
    {
        $sanitized = [];

        $sanitized['company_name'] = sanitize_text_field($input['company_name'] ?? '');
        $sanitized['contact_phone'] = sanitize_text_field($input['contact_phone'] ?? '');
        $sanitized['contact_email'] = sanitize_email($input['contact_email'] ?? '');
        $sanitized['whatsapp_number'] = preg_replace('/[^0-9]/', '', (string) ($input['whatsapp_number'] ?? ''));
        $sanitized['company_address'] = sanitize_textarea_field($input['company_address'] ?? '');
        $sanitized['booking_email'] = sanitize_email($input['booking_email'] ?? '');
        $sanitized['hero_title'] = sanitize_text_field($input['hero_title'] ?? '');
        $sanitized['hero_subtitle'] = sanitize_textarea_field($input['hero_subtitle'] ?? '');
        $featuredRaw = array_filter(array_map('absint', explode(',', (string) ($input['featured_package_ids'] ?? ''))));
        $featuredValidated = [];
        foreach ($featuredRaw as $featuredId) {
            if (get_post_type($featuredId) === 'pwt_package' && get_post_status($featuredId) === 'publish') {
                $featuredValidated[] = $featuredId;
            }
        }
        $sanitized['featured_package_ids'] = implode(',', $featuredValidated);
        $sanitized['google_analytics_id'] = sanitize_text_field($input['google_analytics_id'] ?? '');

        return $sanitized;
    }

    /**
     * Sanitize Payments settings.
     */
    public function sanitizePayments(array $input): array
    {
        $sanitized = [];

        $sanitized['payment_page_url'] = esc_url_raw($input['payment_page_url'] ?? '');
        $sanitized['payment_upi_id'] = sanitize_text_field($input['payment_upi_id'] ?? '');
        $sanitized['payment_gateway'] = sanitize_key($input['payment_gateway'] ?? 'manual');
        $sanitized['payment_gateway_checkout_url'] = esc_url_raw($input['payment_gateway_checkout_url'] ?? '');
        $sanitized['payment_methods'] = sanitize_text_field($input['payment_methods'] ?? 'upi,bank_transfer,cash');
        $sanitized['payment_advance_percent'] = max(1, min(100, absint($input['payment_advance_percent'] ?? 30)));
        $sanitized['payment_instructions'] = sanitize_textarea_field($input['payment_instructions'] ?? '');

        return $sanitized;
    }

    /**
     * Sanitize Availability settings.
     */
    public function sanitizeAvailability(array $input): array
    {
        $sanitized = [];

        $blockedRaw = array_filter(array_map('trim', explode(',', (string) ($input['blocked_dates'] ?? ''))));
        $blockedValidated = [];
        foreach ($blockedRaw as $blockedDate) {
            $parsed = \DateTime::createFromFormat('Y-m-d', $blockedDate);
            if ($parsed instanceof \DateTime && $parsed->format('Y-m-d') === $blockedDate) {
                $blockedValidated[] = $blockedDate;
            }
        }
        $sanitized['blocked_dates'] = implode(',', array_values(array_unique($blockedValidated)));

        $sanitized['daily_booking_limit'] = max(1, absint($input['daily_booking_limit'] ?? 6));
        $sanitized['rest_api_key'] = sanitize_text_field($input['rest_api_key'] ?? '');
        $sanitized['rest_rate_limit_per_minute'] = max(1, absint($input['rest_rate_limit_per_minute'] ?? 20));

        return $sanitized;
    }

    public function renderGeneralPage(): void
    {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('General Settings', 'wildtours-plugin'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('pwt_settings_general');
                echo settings_errors();
                do_settings_sections('pwt-settings-general');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function renderPaymentsPage(): void
    {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Payment Settings', 'wildtours-plugin'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('pwt_settings_payments');
                echo settings_errors();
                do_settings_sections('pwt-settings-payments');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function renderAvailabilityPage(): void
    {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Availability Settings', 'wildtours-plugin'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('pwt_settings_availability');
                echo settings_errors();
                do_settings_sections('pwt-settings-availability');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}