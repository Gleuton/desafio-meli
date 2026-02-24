<?php

namespace App\Core\Application\DTOs\Output;

final class ItemListResponseDTO
{
    /**
     * @param  ItemResponseDTO[]  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $totalItems,
        public readonly int $currentPage,
        public readonly int $perPage,
        public readonly int $totalPages,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'items' => array_map(static fn ($item) => $item->toArray(), $this->items),
            'totalItems' => $this->totalItems,
            'currentPage' => $this->currentPage,
            'perPage' => $this->perPage,
            'totalPages' => $this->totalPages,
        ];
    }
}
