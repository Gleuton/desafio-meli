<?php

namespace App\Core\Application\Exceptions;

/**
 * Exception thrown when job processing fails
 * Usually due to invalid item data or API errors during processing
 */
class FailedToProcessItemException extends ApplicationException
{
    public static function fromApiError(string $itemId, string $previousError, int $attempt = 1, int $maxTries = 3): self
    {
        return new self(
            message: "Failed to process item '{$itemId}' (attempt {$attempt}/{$maxTries}): {$previousError}"
        );
    }

    public static function invalidItemData(string $itemId, string $reason): self
    {
        return new self(
            message: "Invalid item data for item '{$itemId}': {$reason}"
        );
    }

    public static function maxRetriesExceeded(string $itemId, int $maxTries): self
    {
        return new self(
            message: "Item '{$itemId}' failed after {$maxTries} retry attempts"
        );
    }
}
