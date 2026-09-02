<?php
declare(strict_types=1);
namespace PWT\Pricing;
defined('ABSPATH') || exit;

final class SeasonResolver
{
    public function resolve(string $date): array
    {
        $terms = get_terms([
            'taxonomy' => 'pwt_season',
            'hide_empty' => false,
        ]);
        if (is_wp_error($terms)) {
            return [];
        }

        if (!preg_match('/^(\d{4})-(\d{2})(?:-(\d{2}))?$/', trim($date), $m)) {
            return [];
        }

        $year = (int)$m[1];
        $month = (int)$m[2];

        foreach ($terms as $term) {
            $start = $this->boundary($term, 'start');
            $end = $this->boundary($term, 'end');
            if ($start === null || $end === null) {
                continue;
            }

            if ($this->matches($start, $end, $year, $month)) {
                return ['id'=>(int)$term->term_id,'name'=>$term->name,'slug'=>$term->slug];
            }
        }

        return [];
    }

    /**
     * Read the boundary meta for an edge. Prefers the SCF field keys and
     * falls back to the legacy prefixed keys.
     *
     * @return array{kind:string,value:int|array{y:int,m:int}}|null
     */
    private function boundary(\WP_Term $term, string $edge): ?array
    {
        foreach (["season_{$edge}", "_pwt_season_{$edge}"] as $key) {
            $value = trim((string)get_term_meta($term->term_id, $key, true));
            if ($value === '') {
                continue;
            }
            $parsed = $this->parse($value);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        return null;
    }

    /**
     * Parse a stored boundary value as a recurring month (1-12) or an
     * absolute year-month.
     *
     * @return array{kind:string,value:int|array{y:int,m:int}}|null
     */
    private function parse(string $value): ?array
    {
        if (preg_match('/^\d{1,2}$/', $value)) {
            $month = (int)$value;
            return ($month >= 1 && $month <= 12) ? ['kind'=>'month','value'=>$month] : null;
        }

        if (preg_match('/^(\d{4})-?(\d{2})/', $value, $m)) {
            $year = (int)$m[1];
            $month = (int)$m[2];
            if ($month >= 1 && $month <= 12) {
                return ['kind'=>'date','value'=>['y'=>$year,'m'=>$month]];
            }
        }

        return null;
    }

    private function matches(array $start, array $end, int $year, int $month): bool
    {
        if ($start['kind'] === 'month' && $end['kind'] === 'month') {
            return $this->monthInRange((int)$start['value'], (int)$end['value'], $month);
        }

        if ($start['kind'] === 'date' && $end['kind'] === 'date') {
            $startAbs = (int)($start['value']['y'] ?? 0) * 12 + ((int)($start['value']['m'] ?? 1) - 1);
            $endAbs = (int)($end['value']['y'] ?? 0) * 12 + ((int)($end['value']['m'] ?? 1) - 1);
            $currentAbs = $year * 12 + ($month - 1);
            return $currentAbs >= $startAbs && $currentAbs <= $endAbs;
        }

        return false;
    }

    private function monthInRange(int $start, int $end, int $month): bool
    {
        if ($start <= $end) {
            return $month >= $start && $month <= $end;
        }

        return $month >= $start || $month <= $end;
    }
}