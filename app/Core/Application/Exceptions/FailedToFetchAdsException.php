<?php

namespace App\Core\Application\Exceptions;

/**
 * Exception thrown when fetching ads from Meli API fails
 * Usually due to network issues, timeouts, or API errors
 */
class FailedToFetchAdsException extends ApplicationException
{
    public static function fromApiError(string $sellerId, string $previousError): self
    {
        return new self(
            message: "Failed to fetch ads from Meli API for seller '{$sellerId}': {$previousError}",
            code: 0,
            previous: null
        );
    }

    public static function invalidSellerOrToken(string $sellerId): self
    {
        return new self(
            message: "Invalid seller ID or access token for seller '{$sellerId}'",
            code: 401
        );
    }

    public static function networkTimeout(string $sellerId): self
    {
        return new self(
            message: "Network timeout while fetching ads for seller '{$sellerId}'",
            code: 408
        );
    }
}
