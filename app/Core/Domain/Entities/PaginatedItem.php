<?php

namespace App\Core\Domain\Entities;

use InvalidArgumentException;

class PaginatedItem
{
    /**
     * @var array<int, Item>
     */
    public private(set) array $items = [];

    public private(set) int $totalItems;

    public private(set) int $currentPage;

    public private(set) int $perPage;

    public private(set) int $totalPages;

    /**
     * @param  array<int, Item>  $items
     */
    public function __construct(
        array $items,
        int $totalItems,
        int $currentPage,
        int $perPage,
    ) {
        foreach ($items as $item) {
            $this->validate($item);
            $this->items[] = $item;
        }

        $this->totalItems = $totalItems;
        $this->currentPage = $currentPage;
        $this->perPage = $perPage;
        $this->totalPages = $this->totalPages();
    }

    private function totalPages(): int
    {
        return (int) ceil($this->totalItems / $this->perPage);
    }

    private function validate(mixed $item): void
    {
        if (! $item instanceof Item) {
            throw new InvalidArgumentException('Invalid item type');
        }
    }
}
