<?php
declare(strict_types=1);
namespace PWT\Bookings;
defined('ABSPATH') || exit;

use PWT\Bookings\BookingStatus;

/**
 * HTML email templates for the booking lifecycle.
 *
 * Transport stays with wp_mail(); an SMTP/API plugin (e.g. GoSMTP, FluentSMTP)
 * may be used to configure delivery without any plugin-side changes.
 */
final class EmailTemplates
{
    public static function adminEnquirySubject(array $ctx): string
    {
        return (string) apply_filters('pwt/email/admin_enquiry_subject', sprintf(
            /* translators: %s: booking number */
            __('New Booking Enquiry %s', 'wildtours-plugin'),
            (string) ($ctx['booking_number'] ?? '')
        ), $ctx);
    }

    public static function acknowledgementSubject(array $ctx): string
    {
        return (string) apply_filters('pwt/email/acknowledgement_subject', sprintf(
            /* translators: %s: customer first name */
            __('We received your request %s', 'wildtours-plugin'),
            (string) ($ctx['customer']['first_name'] ?? '')
        ), $ctx);
    }

    public static function confirmationSubject(array $ctx): string
    {
        return (string) apply_filters('pwt/email/confirmation_subject', sprintf(
            /* translators: %s: booking number */
            __('Booking Confirmed %s', 'wildtours-plugin'),
            (string) ($ctx['booking_number'] ?? '')
        ), $ctx);
    }

    public static function paymentReminderSubject(array $ctx): string
    {
        return (string) apply_filters('pwt/email/payment_reminder_subject', sprintf(
            /* translators: %s: booking number */
            __('Payment Reminder %s', 'wildtours-plugin'),
            (string) ($ctx['booking_number'] ?? '')
        ), $ctx);
    }

    public static function cancellationSubject(array $ctx): string
    {
        return (string) apply_filters('pwt/email/cancellation_subject', sprintf(
            /* translators: %s: booking number */
            __('Booking Cancelled %s', 'wildtours-plugin'),
            (string) ($ctx['booking_number'] ?? '')
        ), $ctx);
    }

    public static function voucherSubject(array $ctx): string
    {
        return (string) apply_filters('pwt/email/voucher_subject', sprintf(
            /* translators: %s: booking number */
            __('Your Travel Voucher %s', 'wildtours-plugin'),
            (string) ($ctx['booking_number'] ?? '')
        ), $ctx);
    }

    public static function adminEnquiryBody(array $ctx): string
    {
        $customer = (array) ($ctx['customer'] ?? []);

        $content = '<p>' . esc_html__('A new booking enquiry has been submitted.', 'wildtours-plugin') . '</p>'
            . self::block($ctx)
            . '<p>' . esc_html__('Open this booking in the operations dashboard to respond.', 'wildtours-plugin') . '</p>';

        return self::layout(
            (string) ($customer['first_name'] ?? ''),
            $content,
            __('New booking enquiry', 'wildtours-plugin')
        );
    }

    public static function acknowledgementBody(array $ctx): string
    {
        $customer = (array) ($ctx['customer'] ?? []);

        $content = '<p>' . sprintf(
            /* translators: %s: customer first name */
            esc_html__('Thank you, %s. Your booking request has been received and our team will revert shortly.', 'wildtours-plugin'),
            esc_html((string) ($customer['first_name'] ?? ''))
        ) . '</p>'
            . self::block($ctx);

        if (!empty($ctx['payment']['payment_link'])) {
            $content .= self::paymentCta(
                (string) $ctx['payment']['payment_link'],
                (string) ($ctx['payment']['advance_amount'] ?? '')
            );
        }

        return self::layout($customer['email'] ?? '', $content, __('Booking request received', 'wildtours-plugin'));
    }

    public static function confirmationBody(array $ctx): string
    {
        $content = '<p>' . esc_html__('Great news! Your booking has been confirmed.', 'wildtours-plugin') . '</p>'
            . self::block($ctx)
            . self::itemsTable((array) ($ctx['items'] ?? []));

        return self::layout('', $content, __('Booking confirmed', 'wildtours-plugin'));
    }

    public static function paymentReminderBody(array $ctx): string
    {
        $content = '<p>' . esc_html__('Your booking is awaiting the advance payment to be finalised.', 'wildtours-plugin') . '</p>'
            . self::block($ctx);

        if (!empty($ctx['payment']['payment_link'])) {
            $content .= self::paymentCta(
                (string) $ctx['payment']['payment_link'],
                (string) ($ctx['payment']['advance_amount'] ?? '')
            );
        }

        return self::layout('', $content, __('Payment reminder', 'wildtours-plugin'));
    }

    public static function cancellationBody(array $ctx): string
    {
        $content = '<p>' . esc_html__('Your booking has been cancelled.', 'wildtours-plugin') . '</p>'
            . self::block($ctx)
            . '<p>' . esc_html__('If you have made any payments, the refund will be processed according to the cancellation policy.', 'wildtours-plugin') . '</p>';

        return self::layout('', $content, __('Booking cancelled', 'wildtours-plugin'));
    }

    public static function voucherBody(array $ctx): string
    {
        $content = '<p>' . esc_html__('Attached are the details for your upcoming trip. Please keep this voucher handy.', 'wildtours-plugin') . '</p>'
            . self::block($ctx)
            . self::itemsTable((array) ($ctx['items'] ?? []))
            . self::travelersTable((array) ($ctx['travelers'] ?? []));

        return self::layout('', $content, __('Travel voucher', 'wildtours-plugin'));
    }

    private static function block(array $ctx): string
    {
        $customer = (array) ($ctx['customer'] ?? []);
        $rows = [
            [__('Booking Number', 'wildtours-plugin'), (string) ($ctx['booking_number'] ?? '')],
            [__('Status', 'wildtours-plugin'), BookingStatus::label((string) ($ctx['status'] ?? 'pending'))],
            [__('Travel Dates', 'wildtours-plugin'), self::dateRange((string) ($ctx['travel_start'] ?? ''), (string) ($ctx['travel_end'] ?? ''))],
            [__('Travelers', 'wildtours-plugin'), (string) ((int) ($ctx['adults'] ?? 0) + (int) ($ctx['children'] ?? 0))],
        ];

        if (!empty($customer['name'])) {
            array_unshift($rows, [__('Customer', 'wildtours-plugin'), (string) $customer['name']]);
        }
        if (!empty($customer['email'])) {
            $rows[] = [__('Email', 'wildtours-plugin'), (string) $customer['email']];
        }
        if (!empty($customer['phone'])) {
            $rows[] = [__('Phone', 'wildtours-plugin'), (string) $customer['phone']];
        }
        if (isset($ctx['total'])) {
            $rows[] = [__('Total', 'wildtours-plugin'), self::amount((float) $ctx['total'], (string) ($ctx['currency'] ?? 'INR'))];
        }
        if (isset($ctx['balance_due']) && (float) $ctx['balance_due'] > 0) {
            $rows[] = [__('Balance Due', 'wildtours-plugin'), self::amount((float) $ctx['balance_due'], (string) ($ctx['currency'] ?? 'INR'))];
        }

        return self::table($rows);
    }

    private static function itemsTable(array $items): string
    {
        if (!$items) {
            return '';
        }

        $rows = [[
            __('Item', 'wildtours-plugin'),
            __('Date', 'wildtours-plugin'),
            __('Qty', 'wildtours-plugin'),
            __('Total', 'wildtours-plugin'),
        ]];
        foreach ($items as $item) {
            $rows[] = [
                (string) ($item['name'] ?? ''),
                self::dateRange((string) ($item['start_date'] ?? ''), (string) ($item['end_date'] ?? '')),
                (string) ($item['quantity'] ?? 1),
                self::amount((float) ($item['total'] ?? 0), (string) ($item['currency'] ?? 'INR')),
            ];
        }

        return '<h3>' . esc_html__('Booking Items', 'wildtours-plugin') . '</h3>' . self::table($rows);
    }

    private static function travelersTable(array $travelers): string
    {
        if (!$travelers) {
            return '';
        }

        $rows = [[__('Traveler', 'wildtours-plugin'), __('Passport', 'wildtours-plugin'), __('Nationality', 'wildtours-plugin')]];
        foreach ($travelers as $traveler) {
            $rows[] = [
                trim((string) ($traveler['first_name'] ?? '') . ' ' . (string) ($traveler['last_name'] ?? '')),
                (string) ($traveler['passport_number'] ?? ''),
                (string) ($traveler['nationality'] ?? ''),
            ];
        }

        return '<h3>' . esc_html__('Travelers', 'wildtours-plugin') . '</h3>' . self::table($rows);
    }

    private static function paymentCta(string $url, string $amountLabel): string
    {
        $label = $amountLabel !== ''
            ? sprintf(
                /* translators: %s: formatted advance amount */
                __('Pay %s now', 'wildtours-plugin'),
                $amountLabel
            )
            : __('Make a payment', 'wildtours-plugin');

        return '<p><a href="' . esc_url($url) . '" style="display:inline-block;background:#0a7d4f;color:#ffffff;padding:12px 20px;border-radius:6px;text-decoration:none;font-weight:bold;">'
            . esc_html($label) . '</a></p>';
    }

    private static function table(array $rows): string
    {
        $html = '<table style="width:100%;border-collapse:collapse;margin:8px 0;">';
        foreach ($rows as $index => $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $style = $index === 0
                    ? 'padding:8px;text-align:left;background:#f3f5f7;border-bottom:1px solid #d9dee3;font-size:13px;'
                    : 'padding:8px;text-align:left;border-bottom:1px solid #eef1f4;font-size:13px;';
                $html .= '<td style="' . $style . '">' . esc_html((string) $cell) . '</td>';
            }
            $html .= '</tr>';
        }
        return $html . '</table>';
    }

    private static function layout(string $toEmail, string $content, string $title): string
    {
        $siteName = wp_specialchars_decode((string) get_option('blogname'), ENT_QUOTES);
        $subjectTitle = esc_html($title);

        $html = '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#eef1f4;font-family:Arial,Helvetica,sans-serif;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef1f4;">'
            . '<tr><td align="center" style="padding:24px 12px;">'
            . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">'
            . '<tr><td style="padding:16px 24px;background:#0a7d4f;border-radius:8px 8px 0 0;">'
            . '<span style="color:#ffffff;font-size:18px;font-weight:bold;">' . esc_html($siteName) . '</span>'
            . '</td></tr>'
            . '<tr><td style="padding:24px;background:#ffffff;border-radius:0 0 8px 8px;">'
            . '<h2 style="margin-top:0;color:#1a2b3c;">' . $subjectTitle . '</h2>'
            . $content
            . '<p style="margin-top:24px;color:#8a94a0;font-size:12px;">'
            . esc_html__('This is a system generated email. Please do not reply to this message.', 'wildtours-plugin')
            . '</p></td></tr>'
            . '</table></td></tr></table></body></html>';

        return (string) apply_filters('pwt/email/html_layout', $html, $toEmail, $title);
    }

    private static function dateRange(string $start, string $end): string
    {
        if ($start === '' && $end === '') {
            return '';
        }
        $fmt = static fn (string $d): string => date_i18n('j M Y', strtotime($d));
        return $start === $end || $end === '' ? $fmt($start) : $fmt($start) . ' - ' . $fmt($end);
    }

    private static function amount(float $value, string $currency): string
    {
        return esc_html($currency . ' ' . number_format_i18n($value, 2));
    }
}