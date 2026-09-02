<?php

declare(strict_types=1);

namespace PWT\Pricing;

defined('ABSPATH') || exit;

use DateTimeImmutable;
use Throwable;
use PWT\Settings\Settings;

final class PricingService
{
    public const CACHE_KEY_PREFIX = 'pwt_pricing';

    public const DEFAULT_CURRENCY = 'INR';

    public function __construct(
        private readonly RateRepository $rates,
        private readonly SeasonResolver $seasons
    ) {
    }

    /**
     * Shortcode:
     *
     * [upi_qr_code amount="1500"]
     *
     * The amount is treated as display/payment intent only.
     * A real booking payment should use the server-side booking amount,
     * not a user-controlled shortcode attribute.
     */
    public function upi_qr_code_shortcode(
        array $attributes = []
    ): string {
        $attributes = shortcode_atts(
            [
                'amount' => '0',
                'resource_type' => 'safari',
                'resource_id' => '0',
                'date' => wp_date('Y-m-d'),
                'reference' => '',
            ],
            $attributes,
            'upi_qr_code'
        );

        $amount = $this->normalizeAmount(
            (float) $attributes['amount']
        );

        if ($amount <= 0) {
            return '';
        }

        $resourceType = sanitize_key(
            (string) $attributes['resource_type']
        );

        $resourceId = absint(
            $attributes['resource_id']
        );

        $date = $this->normalizeDate(
            (string) $attributes['date']
        );

        if ($date === '') {
            $date = wp_date('Y-m-d');
        }

        $reference = sanitize_text_field(
            (string) $attributes['reference']
        );

        if ($reference === '') {
            $reference = $this->generateReferenceId(
                $resourceId,
                $resourceType
            );
        }

        $upiUrl = $this->generateUPIPaymentUrl(
            $amount,
            $reference,
            sprintf(
                '%s payment for %s',
                $resourceType !== ''
                    ? $resourceType
                    : 'booking',
                $date
            )
        );

        if ($upiUrl === '') {
            return '';
        }

        $amountDisplay = number_format_i18n(
            $amount,
            2
        );

        return sprintf(
            '<div class="upi-qr-code" data-reference="%1$s">
                <p class="upi-payment-amount">%2$s %3$s</p>
                <a href="%4$s" class="upi-payment-button">
                    %5$s
                </a>
            </div>',
            esc_attr($reference),
            esc_html($this->getCurrency()),
            esc_html($amountDisplay),
            esc_url($upiUrl),
            esc_html__(
                'Pay via UPI',
                'wildtours-plugin'
            )
        );
    }

    /**
     * Generate a server-side pricing quote.
     *
     * This method intentionally keeps the existing public signature so
     * BookingOrchestrator and other existing callers remain compatible.
     */
    public function quote(
        int $resourceId,
        string $resourceType,
        string $date,
        int $quantity = 1,
        float $fallback = 0.0
    ): array {
        $resourceId = absint($resourceId);

        $resourceType = sanitize_key(
            $resourceType
        );

        $normalizedDate = $this->normalizeDate(
            $date
        );

        $quantity = max(
            1,
            $quantity
        );

        $fallback = $this->normalizeAmount(
            $fallback
        );

        if (
            !$resourceId
            || $resourceType === ''
            || $normalizedDate === ''
        ) {
            return $this->invalidQuote(
                $resourceId,
                $resourceType,
                $date,
                $quantity,
                $fallback
            );
        }

        $cacheKey = $this->cacheKey(
            $resourceId,
            $resourceType,
            $normalizedDate,
            $quantity,
            $fallback
        );

        $cached = wp_cache_get(
            $cacheKey,
            self::CACHE_KEY_PREFIX
        );

        if (
            is_array($cached)
            && isset(
                $cached['unit_price'],
                $cached['total']
            )
        ) {
            return $this->normalizeQuote(
                $cached,
                $resourceId,
                $resourceType,
                $normalizedDate,
                $quantity,
                $fallback
            );
        }

        $matchedRate = null;

        $unitPrice = $fallback;

        $rows = $this->rates->find(
            $resourceId,
            $resourceType,
            $normalizedDate
        );

        if (!is_array($rows)) {
            $rows = [];
        }

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            if (
                !$this->matchesQuantity(
                    $row,
                    $quantity
                )
            ) {
                continue;
            }

            $amount = $this->normalizeAmount(
                (float) (
                    $row['amount'] ?? 0
                )
            );

            $unitPrice = $amount;
            $matchedRate = $row;

            break;
        }

        $unitPrice = $this->normalizeAmount(
            $unitPrice
        );

        $subtotal = $this->multiplyMoney(
            $unitPrice,
            $quantity
        );

        $currency = strtoupper(
            sanitize_text_field(
                (string) (
                    $matchedRate['currency']
                    ?? $this->getCurrency()
                )
            )
        );

        if ($currency === '') {
            $currency = $this->getCurrency();
        }


        $season = $this->resolveSeason(
            $normalizedDate
        );

        $rateId = absint(
            $matchedRate['id'] ?? 0
        );

        $referenceId = $this->generateReferenceId(
            $resourceId,
            $resourceType
        );

        $quote = [
            'valid' => ($matchedRate !== null || $fallback > 0.0),

            'resource_id' => $resourceId,

            'resource_type' => $resourceType,

            'date' => $normalizedDate,

            'quantity' => $quantity,

            /*
             * Canonical pricing fields.
             */
            'unit_price' => $unitPrice,

            'subtotal' => $subtotal,

            'discount' => 0.0,

            'tax' => 0.0,

            'total' => $subtotal,

            'currency' => $currency,

            'rate_id' => $rateId,

            'season' => $season,

            'reference_id' => $referenceId,

            /*
             * Detailed calculation data for admin booking details,
             * REST/API consumers, invoices and future frontend UI.
             */
            'breakdown' => [
                [
                    'type' => 'base_rate',
                    'label' => __(
                        'Base rate',
                        'wildtours-plugin'
                    ),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'amount' => $subtotal,
                    'rate_id' => $rateId,
                ],
            ],

            /*
             * This is a UPI deep link, not a proof of payment.
             * Payment must still be verified by the payment layer.
             */
            'upi_qr_code' => $this->generateUPIPaymentUrl(
                $subtotal,
                $referenceId,
                sprintf(
                    '%s booking on %s',
                    $resourceType,
                    $normalizedDate
                )
            ),
        ];

        /*
         * Cache the complete quote structure.
         *
         * Do not cache only the unit price because callers also need
         * rate_id, currency, season and calculation details.
         */
        wp_cache_set(
            $cacheKey,
            $quote,
            self::CACHE_KEY_PREFIX,
            HOUR_IN_SECONDS
        );

        return $quote;
    }

    /**
     * Clear cached quotes.
     *
     * Call this after changing a rate or seasonal pricing.
     */
    public function flushQuoteCache(
        ?int $resourceId = null,
        ?string $resourceType = null
    ): void {
        /*
         * WordPress object cache does not provide reliable wildcard
         * deletion across all cache implementations.
         *
         * A version key gives us safe cache invalidation.
         */
        $version = (int) get_option(
            'pwt_pricing_cache_version',
            1
        );

        update_option(
            'pwt_pricing_cache_version',
            $version + 1,
            false
        );
    }

    private function invalidQuote(
        int $resourceId,
        string $resourceType,
        string $date,
        int $quantity,
        float $fallback
    ): array {
        return [
            'valid' => false,

            'resource_id' => $resourceId,

            'resource_type' => $resourceType,

            'date' => sanitize_text_field($date),

            'quantity' => max(1, $quantity),

            'unit_price' => $fallback,

            'subtotal' => 0.0,

            'discount' => 0.0,

            'tax' => 0.0,

            'total' => 0.0,

            'rate_id' => 0,

            'currency' => $this->getCurrency(),

            'season' => null,

            'reference_id' => '',

            'breakdown' => [],

            'upi_qr_code' => '',
        ];
    }

    private function normalizeQuote(
        array $quote,
        int $resourceId,
        string $resourceType,
        string $date,
        int $quantity,
        float $fallback
    ): array {
        $quote['valid'] = !empty(
            $quote['valid']
        );

        $quote['resource_id'] = absint(
            $quote['resource_id']
            ?? $resourceId
        );

        $quote['resource_type'] = sanitize_key(
            (string) (
                $quote['resource_type']
                ?? $resourceType
            )
        );

        $quote['date'] = $this->normalizeDate(
            (string) (
                $quote['date']
                ?? $date
            )
        );

        $quote['quantity'] = max(
            1,
            (int) (
                $quote['quantity']
                ?? $quantity
            )
        );

        $quote['unit_price'] = $this->normalizeAmount(
            (float) (
                $quote['unit_price']
                ?? $fallback
            )
        );

        $quote['subtotal'] = $this->normalizeAmount(
            (float) (
                $quote['subtotal']
                ?? (
                    $quote['unit_price']
                    * $quote['quantity']
                )
            )
        );

        $quote['discount'] = $this->normalizeAmount(
            (float) (
                $quote['discount']
                ?? 0
            )
        );

        $quote['tax'] = $this->normalizeAmount(
            (float) (
                $quote['tax']
                ?? 0
            )
        );

        $quote['total'] = $this->normalizeAmount(
            (float) (
                $quote['total']
                ?? (
                    $quote['subtotal']
                    - $quote['discount']
                    + $quote['tax']
                )
            )
        );

        $quote['rate_id'] = absint(
            $quote['rate_id'] ?? 0
        );

        $quote['currency'] = strtoupper(
            sanitize_text_field(
                (string) (
                    $quote['currency']
                    ?? $this->getCurrency()
                )
            )
        );

        if ($quote['currency'] === '') {
            $quote['currency'] = $this->getCurrency();
        }

        $quote['reference_id'] = sanitize_text_field(
            (string) (
                $quote['reference_id']
                ?? ''
            )
        );

        if ($quote['reference_id'] === '') {
            $quote['reference_id'] = $this->generateReferenceId(
                $quote['resource_id'],
                $quote['resource_type']
            );
        }

        if (
            !isset($quote['breakdown'])
            || !is_array($quote['breakdown'])
        ) {
            $quote['breakdown'] = [];
        }

        if (!isset($quote['upi_qr_code'])) {
            $quote['upi_qr_code'] =
                $this->generateUPIPaymentUrl(
                    $quote['total'],
                    $quote['reference_id'],
                    sprintf(
                        '%s booking on %s',
                        $quote['resource_type'],
                        $quote['date']
                    )
                );
        }

        return $quote;
    }

    private function matchesQuantity(
        array $row,
        int $quantity
    ): bool {
        $minimum = max(
            1,
            (int) (
                $row['min_quantity'] ?? 1
            )
        );

        $maximum = (
            isset($row['max_quantity'])
            && $row['max_quantity'] !== null
            && $row['max_quantity'] !== ''
        )
            ? max(
                $minimum,
                (int) $row['max_quantity']
            )
            : PHP_INT_MAX;

        return (
            $quantity >= $minimum
            && $quantity <= $maximum
        );
    }

    private function resolveSeason(
        string $date
    ): mixed {
        try {
            return $this->seasons->resolve(
                $date
            );
        } catch (Throwable) {
            /*
             * A pricing quote should not fatally fail because optional
             * season metadata cannot be resolved.
             */
            return null;
        }
    }

    private function cacheKey(
        int $resourceId,
        string $resourceType,
        string $date,
        int $quantity,
        float $fallback
    ): string {
        $version = (int) get_option(
            'pwt_pricing_cache_version',
            1
        );

        return implode(
            '_',
            [
                self::CACHE_KEY_PREFIX,
                'v' . max(1, $version),
                sanitize_key($resourceType),
                $resourceId,
                $date,
                'q' . $quantity,
                'f' . md5(
                    wp_json_encode(
                        $fallback
                    )
                ),
            ]
        );
    }

    private function normalizeDate(
        string $date
    ): string {
        $date = sanitize_text_field(
            trim($date)
        );

        if ($date === '') {
            return '';
        }

        try {
            $parsed = new DateTimeImmutable(
                $date
            );
        } catch (Throwable) {
            return '';
        }

        return $parsed->format(
            'Y-m-d'
        );
    }

    private function normalizeAmount(
        float $amount
    ): float {
        if (!is_finite($amount)) {
            return 0.0;
        }

        return round(
            max(0.0, $amount),
            2
        );
    }

    private function multiplyMoney(
        float $unitPrice,
        int $quantity
    ): float {
        return round(
            $unitPrice * max(1, $quantity),
            2
        );
    }

    private function generateReferenceId(
        int $resourceId,
        string $resourceType
    ): string {
        return sprintf(
            'PWT-%s-%d-%s',
            gmdate('YmdHis'),
            max(0, $resourceId),
            strtoupper(
                wp_generate_password(
                    8,
                    false,
                    false
                )
            )
        );
    }

    private function generateUPIPaymentUrl(
        float $amount,
        string $referenceId,
        string $transactionNote
    ): string {
        $amount = $this->normalizeAmount(
            $amount
        );

        $vpa = $this->getUpiVpa();

        if (
            $amount <= 0
            || $vpa === ''
            || strpos($vpa, '@') === false
        ) {
            return '';
        }

        $parameters = [
            'pa' => $vpa,
            'pn' => $this->getUpiPayeeName(),
            'am' => number_format(
                $amount,
                2,
                '.',
                ''
            ),
            'cu' => $this->getCurrency(),
            'tr' => $referenceId,
            'tn' => $transactionNote,
        ];

        return 'upi://pay?' . http_build_query(
            $parameters,
            '',
            '&',
            PHP_QUERY_RFC3986
        );
    }

    private function getCurrency(): string
    {
        $currency = strtoupper(
            sanitize_text_field(
                (string) Settings::currency()
            )
        );

        return $currency !== ''
            ? $currency
            : self::DEFAULT_CURRENCY;
    }

    private function getUpiVpa(): string
    {
        return sanitize_text_field(
            trim(
                (string) Settings::upiVpa()
            )
        );
    }

    private function getUpiPayeeName(): string
    {
        $name = sanitize_text_field(
            trim(
                (string) Settings::upiPayeeName()
            )
        );

        return $name !== ''
            ? $name
            : get_bloginfo('name');
    }
}