<?php

declare(strict_types=1);

namespace PWT\Integrations;

defined('ABSPATH') || exit;

/**
 * Handles general enquiries from the "PWT Contact Form" Fluent Form:
 * stores a recent-enquiries log and emails the admin.
 */
final class FluentContactHandler
{
    public const FORM_TITLE = 'PWT Contact Form';

    public function register(): void
    {
        add_action('fluentform_submission_inserted', [$this, 'handleSubmission'], 20, 3);
    }

    /**
     * @param mixed $entryId
     * @param mixed $formData
     * @param mixed $form
     */
    public function handleSubmission($entryId, $formData, $form): void
    {
        if (FluentForms::formTitle($form) !== self::FORM_TITLE) {
            return;
        }

        $data = FluentForms::normalizeData($formData);

        $name = sanitize_text_field(FluentForms::field($data, 'name', ['full_name', 'your_name']));
        $email = sanitize_email(FluentForms::field($data, 'email', ['email_address']));
        $phone = sanitize_text_field(FluentForms::field($data, 'phone', ['phone_number', 'mobile']));
        $message = sanitize_textarea_field(FluentForms::field($data, 'message', ['enquiry', 'comments', 'details', 'textarea']));

        if ($name === '' && $email === '') {
            return;
        }

        $log = (array) get_option('pwt_contact_enquiries', []);

        $log[] = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'message' => $message,
            'date' => current_time('mysql'),
        ];

        update_option('pwt_contact_enquiries', array_slice($log, -200, 200));

        if (is_email($email) || $name !== '') {
            wp_mail(
                get_option('admin_email'),
                sprintf(
                    /* translators: %s: enquirer name */
                    __('New Contact Enquiry from %s', 'wildtours-plugin'),
                    $name !== '' ? $name : $email
                ),
                implode("\n", [
                    __('Name:', 'wildtours-plugin') . ' ' . $name,
                    __('Email:', 'wildtours-plugin') . ' ' . $email,
                    __('Phone:', 'wildtours-plugin') . ' ' . $phone,
                    '',
                    __('Message:', 'wildtours-plugin'),
                    $message,
                ])
            );
        }
    }
}