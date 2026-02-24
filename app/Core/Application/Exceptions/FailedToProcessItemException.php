<?php

namespace App\Core\Application\Exceptions;

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
}
