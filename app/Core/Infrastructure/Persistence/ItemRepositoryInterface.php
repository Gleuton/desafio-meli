<?php

namespace App\Core\Infrastructure\Persistence;

interface ItemRepositoryInterface
{
    public function saveFromApi(array $itemData): void;

    public function exists(string $itemId): bool;

    public function count(): int;

    public function createPending(string $itemId): void;

    public function markAsProcessed(string $itemId): void;

    public function markAsFailed(string $itemId, string $reason): void;
}
