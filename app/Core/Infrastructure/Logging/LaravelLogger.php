<?php

namespace App\Core\Infrastructure\Logging;

use App\Core\Application\Contracts\LoggerInterface;
use Illuminate\Support\Facades\Log;

/**
 * @codeCoverageIgnore
 */
class LaravelLogger implements LoggerInterface
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function info(string $message, array $context = []): void
    {
        Log::info($message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function debug(string $message, array $context = []): void
    {
        Log::debug($message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function warning(string $message, array $context = []): void
    {
        Log::warning($message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function error(string $message, array $context = []): void
    {
        Log::error($message, $context);
    }
}
