<?php
declare(strict_types=1);

namespace PWT\Packages;

defined('ABSPATH') || exit;

final class PackageComponentService
{
    public function __construct(
        private readonly ResourceCatalogService $resources
    ) {}
    public function components(
        int $packageId
    ): array {
        $value = get_post_meta(
            $packageId,
            '_pwt_package_components',
            true
        );

        return is_array($value)
            ? array_values($value)
            : [];
    }

    public function save(
        int $packageId,
        array $components
    ): bool {
        $clean = [];

        foreach ($components as $component) {
            if (!is_array($component)) {
                continue;
            }

            $resourceType = sanitize_key(
                (string) (
                    $component['resource_type']
                    ?? ''
                )
            );

            $resourceId = absint(
                $component['resource_id'] ?? 0
            );

            if (
                !$resourceType
                || !$resourceId
                || !$this->resources->isValid($resourceType, $resourceId)
            ) {
                continue;
            }

            $offsetStart = max(
                0,
                (int) (
                    $component['offset_start']
                    ?? 0
                )
            );

            $offsetEnd = max(
                $offsetStart,
                (int) (
                    $component['offset_end']
                    ?? $offsetStart
                )
            );

            $details = $this->resources->details($resourceType, $resourceId);

            $clean[] = [
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'name' => sanitize_text_field(
                    (string) (
                        $component['name']
                        ?? ($details['name'] ?? '')
                    )
                ),
                'quantity' => max(
                    1,
                    absint(
                        $component['quantity']
                        ?? 1
                    )
                ),
                'offset_start' => $offsetStart,
                'offset_end' => $offsetEnd,
                'required' => !empty(
                    $component['required']
                ),
            ];
        }

        return update_post_meta(
            $packageId,
            '_pwt_package_components',
            $clean
        ) !== false;
    }

    public function expand(
        int $packageId,
        string $travelStart,
        string $travelEnd
    ): array {
        try {
            $start = new \DateTimeImmutable(
                $travelStart
            );

            $end = new \DateTimeImmutable(
                $travelEnd ?: $travelStart
            );
        } catch (\Throwable) {
            return [];
        }

        if ($end < $start) {
            $end = $start;
        }

        $items = [];

        foreach (
            $this->components($packageId)
            as $component
        ) {
            $componentStart = $start->modify(
                '+' .
                (int) $component['offset_start'] .
                ' days'
            );

            $componentEnd = $start->modify(
                '+' .
                (int) $component['offset_end'] .
                ' days'
            );

            if ($componentEnd > $end) {
                $componentEnd = $end;
            }

            if ($componentEnd < $componentStart) {
                $componentEnd = $componentStart;
            }

            $items[] = [
                'item_type' =>
                    $component['resource_type'],

                'object_id' =>
                    (int) $component['resource_id'],

                'name' =>
                    $component['name']
                    ?: get_the_title(
                        (int) $component['resource_id']
                    ),

                'quantity' =>
                    max(
                        1,
                        (int) $component['quantity']
                    ),

                'service_date' =>
                    $componentStart->format(
                        'Y-m-d'
                    ),

                'start_date' =>
                    $componentStart->format(
                        'Y-m-d'
                    ),

                'end_date' =>
                    $componentEnd->format(
                        'Y-m-d'
                    ),

                'meta' => [
                    'package_id' => $packageId,
                    'package_component' => true,
                    'required' => (bool) (
                        $component['required']
                        ?? false
                    ),
                ],
            ];
        }

        return $items;
    }

    public function expandRequest(
        array $request
    ): array {
        $packageId = absint(
            $request['package_id'] ?? 0
        );

        if (!$packageId) {
            return $request;
        }

        $travelStart = sanitize_text_field(
            (string) (
                $request['travel_start']
                ?? $request['travel_date']
                ?? ''
            )
        );

        $travelEnd = sanitize_text_field(
            (string) (
                $request['travel_end']
                ?? $travelStart
            )
        );

        if ($travelStart === '') {
            return $request;
        }

        $components = $this->expand(
            $packageId,
            $travelStart,
            $travelEnd
        );

        if (!$components) {
            return $request;
        }

        $existing = is_array(
            $request['items'] ?? null
        )
            ? $request['items']
            : [];

        /*
         * Explicit request items are retained.
         * Package components are appended.
         */
        $request['items'] = array_merge(
            $existing,
            $components
        );

        return $request;
    }
}