<?php

declare(strict_types=1);

namespace PWT\Integrations;

defined('ABSPATH') || exit;

/**
 * Captures subscriptions from the "PWT Newsletter" Fluent Form into the
 * same storage the theme's legacy newsletter form uses.
 */
final class FluentNewsletterHandler
{
    public const FORM_TITLE = 'PWT Newsletter';

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

        $email = sanitize_email(FluentForms::field($data, 'email', ['email_address', 'newsletter_email']));

        if (!is_email($email)) {
            return;
        }

        $subscribers = (array) get_option('wildtours_newsletter_subscribers', []);

        $subscribers[md5($email)] = [
            'email' => $email,
            'date' => current_time('mysql'),
        ];

        update_option(
            'wildtours_newsletter_subscribers',
            array_slice($subscribers, -500, 500, true)
        );
    }
}