<?php

namespace App\Core\Application\DTOs\Output;

final class ItemResponseDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $meliId,
        public readonly string $sellerId,
        public readonly string $title,
        public readonly string $status,
        public readonly string $processingStatus,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?string $processedAt = null,
    ) {}

    /**
     * @return array<string, int|string|null>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'meli_id' => $this->meliId,
            'seller_id' => $this->sellerId,
            'title' => $this->title,
            'status' => $this->status,
            'processing_status' => $this->processingStatus,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'processed_at' => $this->processedAt,
        ];
    }
}
