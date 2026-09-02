<?php

declare(strict_types=1);

namespace PWT\SCF\Contracts;

defined('ABSPATH') || exit;

/**
 * Contract for SCF field groups.
 */
interface FieldGroupInterface
{
    /**
     * Register the field group.
     */
    public function register(): void;
}