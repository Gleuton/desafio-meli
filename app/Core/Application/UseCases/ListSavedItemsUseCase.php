<?php

namespace App\Core\Application\UseCases;

use App\Core\Application\DTOs\Input\ListItemsInputDTO;
use App\Core\Application\DTOs\Output\ItemListResponseDTO;
use App\Core\Application\DTOs\Output\ItemResponseDTO;
use App\Core\Application\Exceptions\InvalidPaginationException;
use App\Core\Infrastructure\Persistence\ItemRepositoryInterface;

final class ListSavedItemsUseCase
{
    private const int MIN_PAGE = 1;

    private const int MAX_PER_PAGE = 100;

    private const int MIN_PER_PAGE = 1;

    public function __construct(
        private readonly ItemRepositoryInterface $repository,
    ) {}

    public function execute(ListItemsInputDTO $input): ItemListResponseDTO
    {
        $this->validatePagination($input);

        $paginatedBySeller = $this->repository->findPaginatedBySeller($input);

        $itemDTOs = array_map(
            static fn ($item) => new ItemResponseDTO(
                $item->id,
                $item->meliId,
                $item->sellerId,
                $item->title,
                $item->status,
                $item->created->format('Y-m-d\TH:i:s\Z'),
                $item->updated->format('Y-m-d\TH:i:s\Z'),
                $item->processedAt?->format('Y-m-d\TH:i:s\Z'),
            ),
            $paginatedBySeller->items
        );

        return new ItemListResponseDTO(
            $itemDTOs,
            $paginatedBySeller->totalItems,
            $paginatedBySeller->currentPage,
            $paginatedBySeller->perPage,
            $paginatedBySeller->totalPages,
        );
    }

    private function validatePagination(ListItemsInputDTO $input): void
    {
        if ($input->page < self::MIN_PAGE) {
            throw InvalidPaginationException::pageInvalid($input->page);
        }

        if ($input->perPage < self::MIN_PER_PAGE) {
            throw InvalidPaginationException::perPageInvalid($input->perPage);
        }

        if ($input->perPage > self::MAX_PER_PAGE) {
            throw InvalidPaginationException::perPageExceeded($input->perPage);
        }
    }
}
