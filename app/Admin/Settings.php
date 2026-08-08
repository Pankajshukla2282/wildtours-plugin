<?php

namespace PWT\Admin;

defined('ABSPATH') || exit;

class Settings
{
    public function register(): void
    {
        add_action(
            'admin_init',
            [$this, 'settings']
        );
    }

    public function settings(): void
    {
        register_setting(
            'pwt_settings_group',
            'pwt_settings',
            [
                'sanitize_callback' => [$this, 'sanitize']
            ]
        );

        add_settings_section(
            'pwt_general',
            __('General Settings', 'wildtours-plugin'),
            '__return_false',
            'pwt-settings'
        );

        add_settings_field(
            'company_name',
            __('Company Name', 'wildtours-plugin'),
            [$this, 'renderTextField'],
            'pwt-settings',
            'pwt_general',
            [
                'key' => 'company_name',
                'placeholder' => __('Panna Wild Tour', 'wildtours-plugin')
            ]
        );

        add_settings_field(
            'contact_phone',
            __('Contact Phone', 'wildtours-plugin'),
            [$this, 'renderTextField'],
            'pwt-settings',
            'pwt_general',
            [
                'key' => 'contact_phone',
                'placeholder' => __('+91 90000 00000', 'wildtours-plugin')
            ]
        );

        add_settings_field(
            'contact_email',
            __('Contact Email', 'wildtours-plugin'),
            [$this, 'renderEmailField'],
            'pwt-settings',
            'pwt_general',
            [
                'key' => 'contact_email',
                'placeholder' => __('hello@example.com', 'wildtours-plugin')
            ]
        );

        add_settings_field(
            'whatsapp_number',
            __('WhatsApp Number', 'wildtours-plugin'),
            [$this, 'renderTextField'],
            'pwt-settings',
            'pwt_general',
            [
                'key' => 'whatsapp_number',
                'placeholder' => __('919000000000', 'wildtours-plugin')
            ]
        );

        add_settings_field(
            'company_address',
            __('Company Address', 'wildtours-plugin'),
            [$this, 'renderTextareaField'],
            'pwt-settings',
            'pwt_general',
            [
                'key' => 'company_address',
                'placeholder' => __('Panna, Madhya Pradesh, India', 'wildtours-plugin')
            ]
        );

        add_settings_field(
            'booking_email',
            __('Booking Notification Email', 'wildtours-plugin'),
            [$this, 'renderEmailField'],
            'pwt-settings',
            'pwt_general',
            [
                'key' => 'booking_email',
                'placeholder' => __('bookings@example.com', 'wildtours-plugin')
            ]
        );

        add_settings_field(
            'hero_title',
            __('Homepage Hero Title', 'wildtours-plugin'),
            [$this, 'renderTextField'],
            'pwt-settings',
            'pwt_general',
            [
                'key' => 'hero_title',
                'placeholder' => __('Explore Panna Tiger Reserve', 'wildtours-plugin')
            ]
        );

        add_settings_field(
            'hero_subtitle',
            __('Homepage Hero Subtitle', 'wildtours-plugin'),
            [$this, 'renderTextareaField'],
            'pwt-settings',
            'pwt_general',
            [
                'key' => 'hero_subtitle',
                'placeholder' => __('Trusted safari planning, premium stays, and complete travel support.', 'wildtours-plugin')
            ]
        );

        add_settings_field(
            'featured_package_ids',
            __('Featured Package IDs (comma separated)', 'wildtours-plugin'),
            [$this, 'renderTextField'],
            'pwt-settings',
            'pwt_general',
            [
                'key' => 'featured_package_ids',
                'placeholder' => __('660,668,675', 'wildtours-plugin')
            ]
        );

        add_settings_field(
            'payment_page_url',
            __('Payment Page URL', 'wildtours-plugin'),
            [$this, 'renderTextField'],
            'pwt-settings',
            'pwt_general',
            [
                'key' => 'payment_page_url',
                'placeholder' => __('https://example.com/payment/', 'wildtours-plugin')
            ]
        );

        add_settings_field(
            'payment_upi_id',
            __('UPI ID', 'wildtours-plugin'),
            [$this, 'renderTextField'],
            'pwt-settings',
            'pwt_general',
            [
                'key' => 'payment_upi_id',
                'placeholder' => __('business@upi', 'wildtours-plugin')
            ]
        );

        add_settings_field(
            'payment_gateway',
            __('Payment Gateway Mode', 'wildtours-plugin'),
            [$this, 'renderSelectField'],
            'pwt-settings',
            'pwt_general',
            [
                'key' => 'payment_gateway',
                'options' => [
                    'manual' => __('Manual Reference (UPI/Bank)', 'wildtours-plugin'),
                    'razorpay' => __('Razorpay (Hosted Redirect)', 'wildtours-plugin'),
                    'cashfree' => __('Cashfree (Hosted Redirect)', 'wildtours-plugin'),
                ],
            ]
        );

        add_settings_field(
            'payment_gateway_checkout_url',
            __('Gateway Checkout URL', 'wildtours-plugin'),
            [$this, 'renderTextField'],
            'pwt-settings',
            'pwt_general',
            [
                'key' => 'payment_gateway_checkout_url',
                'placeholder' => __('https://payments.example.com/checkout', 'wildtours-plugin')
            ]
        );

        add_settings_field(
            'payment_methods',
            __('Allowed Payment Methods (comma separated)', 'wildtours-plugin'),
            [$this, 'renderTextField'],
            'pwt-settings',
            'pwt_general',
            [
                'key' => 'payment_methods',
                'placeholder' => __('upi,bank_transfer,cash', 'wildtours-plugin')
            ]
        );

        add_settings_field(
            'payment_advance_percent',
            __('Advance Payment Percent', 'wildtours-plugin'),
            [$this, 'renderNumberField'],
            'pwt-settings',
            'pwt_general',
            [
                'key' => 'payment_advance_percent',
                'placeholder' => __('30', 'wildtours-plugin')
            ]
        );

        add_settings_field(
            'payment_instructions',
            __('Payment Instructions', 'wildtours-plugin'),
            [$this, 'renderTextareaField'],
            'pwt-settings',
            'pwt_general',
            [
                'key' => 'payment_instructions',
                'placeholder' => __('Share bank or UPI instructions for advance booking confirmation.', 'wildtours-plugin')
            ]
        );

        add_settings_field(
            'blocked_dates',
            __('Blocked Dates (comma separated YYYY-MM-DD)', 'wildtours-plugin'),
            [$this, 'renderTextareaField'],
            'pwt-settings',
            'pwt_general',
            [
                'key' => 'blocked_dates',
                'placeholder' => __('2026-12-25,2026-12-31', 'wildtours-plugin')
            ]
        );

        add_settings_field(
            'daily_booking_limit',
            __('Daily Booking Limit per Package', 'wildtours-plugin'),
            [$this, 'renderNumberField'],
            'pwt-settings',
            'pwt_general',
            [
                'key' => 'daily_booking_limit',
                'placeholder' => __('6', 'wildtours-plugin')
            ]
        );

        add_settings_field(
            'rest_api_key',
            __('REST Booking API Key', 'wildtours-plugin'),
            [$this, 'renderTextField'],
            'pwt-settings',
            'pwt_general',
            [
                'key' => 'rest_api_key',
                'placeholder' => __('Set a long random key for X-PWT-API-KEY header', 'wildtours-plugin')
            ]
        );

        add_settings_field(
            'rest_rate_limit_per_minute',
            __('REST Rate Limit Per Minute', 'wildtours-plugin'),
            [$this, 'renderNumberField'],
            'pwt-settings',
            'pwt_general',
            [
                'key' => 'rest_rate_limit_per_minute',
                'placeholder' => __('20', 'wildtours-plugin')
            ]
        );

        add_settings_field(
            'google_analytics_id',
            __('Google Analytics ID', 'wildtours-plugin'),
            [$this, 'renderTextField'],
            'pwt-settings',
            'pwt_general',
            [
                'key' => 'google_analytics_id',
                'placeholder' => __('G-XXXXXXXXXX', 'wildtours-plugin')
            ]
        );
    }

    public function sanitize(array $input): array
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
        $sanitized['payment_page_url'] = esc_url_raw($input['payment_page_url'] ?? '');
        $sanitized['payment_upi_id'] = sanitize_text_field($input['payment_upi_id'] ?? '');
        $sanitized['payment_gateway'] = sanitize_key($input['payment_gateway'] ?? 'manual');
        $sanitized['payment_gateway_checkout_url'] = esc_url_raw($input['payment_gateway_checkout_url'] ?? '');
        $sanitized['payment_methods'] = sanitize_text_field($input['payment_methods'] ?? 'upi,bank_transfer,cash');
        $sanitized['payment_advance_percent'] = max(1, min(100, absint($input['payment_advance_percent'] ?? 30)));
        $sanitized['payment_instructions'] = sanitize_textarea_field($input['payment_instructions'] ?? '');
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
        $sanitized['google_analytics_id'] = sanitize_text_field($input['google_analytics_id'] ?? '');

        return $sanitized;
    }

    public function renderNumberField(array $args): void
    {
        $this->renderField('number', $args);
    }

    public function renderTextField(array $args): void
    {
        $this->renderField('text', $args);
    }

    public function renderEmailField(array $args): void
    {
        $this->renderField('email', $args);
    }

    public function renderTextareaField(array $args): void
    {
        $options = get_option('pwt_settings', []);
        $key = $args['key'];
        $placeholder = $args['placeholder'] ?? '';
        $value = $options[$key] ?? '';

        ?>
        <textarea
            class="large-text"
            rows="3"
            name="pwt_settings[<?php echo esc_attr($key); ?>]"
            placeholder="<?php echo esc_attr($placeholder); ?>"><?php echo esc_textarea($value); ?></textarea>
        <?php
    }

    public function renderSelectField(array $args): void
    {
        $options = get_option('pwt_settings', []);
        $key = $args['key'];
        $value = (string) ($options[$key] ?? '');
        $choices = $args['options'] ?? [];

        ?>
        <select class="regular-text" name="pwt_settings[<?php echo esc_attr($key); ?>]">
            <?php foreach ($choices as $choiceValue => $choiceLabel) : ?>
                <option value="<?php echo esc_attr((string) $choiceValue); ?>" <?php selected($value, (string) $choiceValue); ?>>
                    <?php echo esc_html((string) $choiceLabel); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    private function renderField(string $type, array $args): void
    {
        $options = get_option('pwt_settings', []);
        $key = $args['key'];
        $placeholder = $args['placeholder'] ?? '';
        $value = $options[$key] ?? '';

        ?>
        <input
            type="<?php echo esc_attr($type); ?>"
            class="regular-text"
            name="pwt_settings[<?php echo esc_attr($key); ?>]"
            placeholder="<?php echo esc_attr($placeholder); ?>"
            value="<?php echo esc_attr($value); ?>">
        <?php
    }
}