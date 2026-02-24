<?php

namespace App\Core\Infrastructure\Persistence;

use App\Models\Item;
use Carbon\Carbon;

class EloquentItemRepository implements ItemRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $itemData
     */
    public function saveFromApi(array $itemData): void
    {
        Item::updateOrCreate(
            ['meli_id' => $itemData['id']],
            [
                'title' => $itemData['title'] ?? null,
                'status' => $itemData['status'] ?? null,
                'processed_at' => Carbon::now(),
                'created' => isset($itemData['created']) ? Carbon::parse($itemData['created']) : null,
                'updated' => isset($itemData['updated']) ? Carbon::parse($itemData['updated']) : null,
            ]
        );
    }

    public function exists(string $itemId): bool
    {
        return Item::where('meli_id', $itemId)->exists();
    }

    public function count(): int
    {
        return Item::count();
    }

    public function createPending(string $itemId): void
    {
        Item::firstOrCreate(
            ['meli_id' => $itemId],
        );
    }

    public function markAsProcessed(string $itemId): void
    {
        Item::where('meli_id', $itemId)->update([
            'processed_at' => Carbon::now(),
        ]);
    }

    public function markAsFailed(string $itemId, string $reason): void
    {
        Item::where('meli_id', $itemId)->update([
            'failed_reason' => $reason,
        ]);
    }
}
