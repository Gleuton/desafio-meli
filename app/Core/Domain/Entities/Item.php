<?php

namespace App\Core\Domain\Entities;

use App\Core\Domain\Enums\ProcessingStatus;
use DateTimeImmutable;

class Item
{
    public private(set) int $id;

    public private(set) string $meliId;

    public private(set) string $sellerId;

    public private(set) string $title;

    public private(set) string $status;

    public private(set) ProcessingStatus $processingStatus;

    public private(set) DateTimeImmutable $created;

    public private(set) DateTimeImmutable $updated;

    public private(set) ?DateTimeImmutable $processedAt;

    public function __construct(
        int $id,
        string $meliId,
        string $sellerId,
        string $title,
        string $status,
        ProcessingStatus $processingStatus,
        DateTimeImmutable $created,
        DateTimeImmutable $updated,
        ?DateTimeImmutable $processedAt = null
    ) {
        $this->id = $id;
        $this->meliId = $meliId;
        $this->sellerId = $sellerId;
        $this->title = $title;
        $this->status = $status;
        $this->processingStatus = $processingStatus;
        $this->created = $created;
        $this->updated = $updated;
        $this->processedAt = $processedAt;
    }
}
