<?php

namespace App\Core\Infrastructure\Persistence\Eloquent;

use App\Core\Domain\Entities\Item;
use App\Core\Domain\Enums\ProcessingStatus;
use App\Models\Item as ItemModel;
use DateTimeImmutable;

final class ItemMapper
{
    public static function toDomain(ItemModel $model): Item
    {
        return new Item(
            id: (int) $model->id,
            meliId: $model->meli_id,
            sellerId: $model->seller_id,
            title: $model->title,
            status: $model->status,
            processingStatus: ProcessingStatus::from($model->processing_status),
            created: new DateTimeImmutable($model->created->toDateTimeString()),
            updated: new DateTimeImmutable($model->updated->toDateTimeString()),
            processedAt: $model->processed_at
                ? new DateTimeImmutable($model->processed_at->toDateTimeString())
                : null,
        );
    }
}
