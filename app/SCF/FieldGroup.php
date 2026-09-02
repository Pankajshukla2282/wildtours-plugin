<?php

declare(strict_types=1);

namespace PWT\SCF;

defined('ABSPATH') || exit;

use PWT\SCF\Contracts\FieldGroupInterface;

/**
 * Base class for SCF/ACF field groups.
 */
abstract class FieldGroup implements FieldGroupInterface
{
    /**
     * Register the field group.
     */
    abstract public function register(): void;

    /**
     * Register a field group with SCF or ACF.
     */
    final protected function addGroup(array $group): void
    {
        $this->validate($group);

        $group = apply_filters(
            'pwt/scf/group',
            $group,
            static::class
        );

        if (function_exists('scf_register_field_group')) {
            scf_register_field_group($group);
            return;
        }

        if (function_exists('acf_add_local_field_group')) {
            acf_add_local_field_group($group);
        }
    }

    /**
     * Validate minimum field group configuration.
     */
    protected function validate(array $group): void
    {
        $required = [
            'key',
            'title',
            'fields',
            'location',
        ];

        foreach ($required as $key) {
            if (!array_key_exists($key, $group)) {
                _doing_it_wrong(
                    static::class,
                    sprintf(
                        __('Missing required field group key: %s', 'wildtours-plugin'),
                        $key
                    ),
                    defined('PWT_VERSION') ? PWT_VERSION : '1.0.0'
                );
            }
        }
    }

    /**
     * Determine whether SCF or ACF is available.
     */
    final protected function isSupported(): bool
    {
        return function_exists('scf_register_field_group')
            || function_exists('acf_add_local_field_group');
    }
}