<?php

namespace App\Core\Infrastructure\Persistence;

use App\Core\Application\DTOs\Input\ListItemsInputDTO;
use App\Core\Domain\Entities\Item;
use App\Core\Domain\Entities\PaginatedItem;

interface ItemRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $itemData
     */
    public function saveFromApi(array $itemData): void;

    public function exists(string $itemId): bool;

    public function count(): int;

    public function createPending(string $itemId, string $sellerId): void;

    public function markAsProcessing(string $itemId): void;

    public function markAsProcessed(string $itemId): void;

    public function markAsFailed(string $itemId, string $reason): void;

    public function findById(string $itemId): ?Item;

    public function findPaginatedBySeller(ListItemsInputDTO $inputDTO): PaginatedItem;
}
