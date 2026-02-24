<?php

namespace App\Core\Application\Exceptions;

final class InvalidPaginationException extends \InvalidArgumentException
{
    public static function pageInvalid(int $page): self
    {
        return new self("Page must be at least 1, got {$page}");
    }

    public static function perPageExceeded(int $perPage): self
    {
        return new self("Per page must not exceed 100, got {$perPage}");
    }

    public static function perPageInvalid(int $perPage): self
    {
        return new self("Per page must be at least 1, got {$perPage}");
    }
}
