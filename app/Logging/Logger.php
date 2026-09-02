<?php

declare(strict_types=1);

namespace PWT\Logging;

defined('ABSPATH') || exit;

final class Logger
{
    public function info(string $message, array $context = []): void
    {
        do_action('pwt/log', 'info', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        do_action('pwt/log', 'warning', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        do_action('pwt/log', 'error', $message, $context);
    }
}