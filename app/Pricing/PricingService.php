<?php
declare(strict_types=1);
namespace PWT\Pricing;
defined('ABSPATH') || exit;

define('HOUR_IN_SECONDS', 60 * 60);

final class PricingService
{
    public function __construct(private readonly RateRepository $rates,
        private readonly SeasonResolver $seasons) {}

    const CACHE_KEY_PREFIX = 'pwt_pricing';
    const UPI_PAYEE = 'pannawildtour';
    const UPI_BANK = 'api@upi';

    /**
     * Shortcode to display UPI payment QR code
     *
     * Usage: [upi_qr_code amount="1500" resource_type="safari" date="2024-12-25"]
     *
     * @param array $attributes Shortcode attributes:
     *   - amount: float - the payment amount in INR
     *   - resource_type: string - 'safari' or 'package'
     *   - date: string - date in Y-m-d format
     *
     * @return string HTML displaying the QR code link and amount
     */
    public function upi_qr_code_shortcode(array $attributes = []): string
    {
        extract(shortcode_atts([
            'amount' => 0,
            'resource_type' => 'safari',
            'date' => date('Y-m-d'),
        ], $attributes));

        $unit = (float)$amount;
        $referenceId = 'pwt_' . mt_rand(1000, 9999) . '_' . $resource_type . '_' . $date;

        $upiUrl = 'upi://pay?pa=' . self::UPI_PAYEE . '@' . self::UPI_BANK
            . '&pn=' . urlencode('Panna Wild Tour')
            . '&mc=' . urlencode('Safari Booking')
            . '&cu=INR'
            . '&ri=' . $referenceId
            . '&tn=' . urlencode('Safari/package payment');

        return '<div class="upi-qr-code">
                    <p>Total: ₹' . number_format($amount, 2) . '</p>
                    <a href="' . $upiUrl . '" target="_blank" class="upi-payment-button">Pay via UPI</a>
                    <p>Scan to pay with UPI</p>
                </div>';
    }

    private function generateUPIQRCode(float $amount, int $resourceId, string $resourceType, string $date): string
    {
        $referenceId = 'pwt_' . mt_rand(1000, 9999) . '_' . $resource_type . '_' . $date;
        $transactionNote = 'Safari/package payment - ' . $resource_type . ' on ' . $date;

        $upiUrl = 'upi://pay?pa=' . self::UPI_PAYEE . '@' . self::UPI_BANK
            . '&pn=' . urlencode('Panna Wild Tour')
            . '&mc=' . urlencode('Safari Booking')
            . '&cu=INR'
            . '&ri=' . $referenceId
            . '&tn=' . urlencode($transactionNote);

        return $upiUrl;
    }

    public function quote(int $resourceId, string $resourceType, string $date, int $quantity = 1, float $fallback = 0.0): array
    {
        $cacheKey = self::CACHE_KEY_PREFIX . '_' . $resourceId . '_' . $resourceType . '_' . $date;
        $unit = wp_cache_get($cacheKey, 'pwt_pricing');
        if (false !== $unit) {
            $unit = (float)$unit;
        } else {
            $quantity = max(1, $quantity);
            $rows = $this->rates->find($resourceId, $resourceType, $date);
            $unit = $fallback;
            $matchedRate = null;

            foreach ($rows as $row) {
                $min = max(1, (int)$row['min_quantity']);
                $max = $row['max_quantity'] !== null ? (int)$row['max_quantity'] : PHP_INT_MAX;
                if ($quantity >= $min && $quantity <= $max) {
                    $unit = (float)$row['amount'];
                    $matchedRate = $row;
                    break;
                }
            }

            wp_cache_set($cacheKey, (string)$unit, 'pwt_pricing', HOUR_IN_SECONDS);
        }

        $quantity = max(1, $quantity);
        $total = round($unit * $quantity, 2);

        return [
            'resource_id' => $resourceId,
            'resource_type' => $resource_type,
            'date' => $date,
            'quantity' => $quantity,
            'unit_price' => $unit,
            'total' => $total,
            'rate_id' => $matchedRate ? (int)$matchedRate['id'] : 0,
            'currency' => $matchedRate['currency'] ?? 'INR',
            'season' => $this->seasons->resolve($date),
            'upi_qr_code' => $this->generateUPIQRCode($unit, mt_rand(1000, 9999), $resource_type, $date),
        ];
    }
}