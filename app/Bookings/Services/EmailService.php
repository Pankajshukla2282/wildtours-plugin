<?php
declare(strict_types=1);
namespace PWT\Bookings\Services;
defined('ABSPATH') || exit;

use PWT\Logging\AuditLog;

/**
 * Transport-agnostic mailer. Delivery is delegated to wp_mail(), so any SMTP
 * or API delivery plugin (GoSMTP, FluentSMTP, WP Mail SMTP, ...) can be used.
 */
final class EmailService
{
    public function __construct(private readonly AuditLog $audit)
    {
    }

    public function sendHtml(string $to, string $subject, string $html, array $options = []): bool
    {
        $to = array_filter(array_map('sanitize_email', (array) $to));
        if (!$to) {
            return false;
        }

        add_filter('wp_mail_from', [$this, 'fromEmail']);
        add_filter('wp_mail_from_name', [$this, 'fromName']);

        $headers = ['Content-Type: text/html; charset=UTF-8'];
        if (!empty($options['reply_to'])) {
            $headers[] = 'Reply-To: ' . sanitize_email((string) $options['reply_to']);
        }

        $sent = wp_mail($to, $subject, $html, $headers);

        remove_filter('wp_mail_from', [$this, 'fromEmail']);
        remove_filter('wp_mail_from_name', [$this, 'fromName']);

        if (!$sent) {
            $this->audit->record(
                'email',
                0,
                'email.failed',
                [
                    'to' => ['to' => implode(',', $to), 'subject' => $subject],
                    'notes' => wp_json_encode($options['context'] ?? []),
                ]
            );
        }

        return $sent;
    }

    public function fromEmail(): string
    {
        $email = (string) apply_filters('pwt/email/from_email', get_option('admin_email'));
        return $email !== '' ? $email : 'noreply@' . wp_parse_url(home_url(), PHP_URL_HOST);
    }

    public function fromName(): string
    {
        return (string) apply_filters('pwt/email/from_name', wp_specialchars_decode((string) get_option('blogname'), ENT_QUOTES));
    }
}