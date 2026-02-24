<?php

declare(strict_types=1);

use App\Core\Application\DTOs\Input\ListItemsInputDTO;
use App\Core\Application\Exceptions\InvalidPaginationException;
use App\Core\Application\UseCases\ListSavedItemsUseCase;
use App\Core\Domain\Entities\Item;
use App\Core\Domain\Entities\PaginatedItem;
use App\Core\Infrastructure\Persistence\ItemRepositoryInterface;
use App\Http\Requests\ListItemsRequest;

function createItem(int $id, string $idItem, string $sellerId): Item
{
    return new Item(
        $id,
        $idItem,
        $sellerId,
        'Sample Product',
        'active',
        new DateTimeImmutable,
        new DateTimeImmutable,
        new DateTimeImmutable,
    );
}

it('executes and returns paginated items', function () {
    $item = createItem(1, 'MLB123', '252254392');
    $collection = new PaginatedItem(
        items: [$item],
        totalItems: 1,
        currentPage: 1,
        perPage: 15
    );

    $repository = Mockery::mock(ItemRepositoryInterface::class);
    $repository->shouldReceive('findPaginatedBySeller')
        ->andReturn($collection);

    $useCase = new ListSavedItemsUseCase($repository);

    $requestMock = Mockery::mock(ListItemsRequest::class);
    $requestMock->shouldReceive('input')->with('page')->andReturn(1);
    $requestMock->shouldReceive('input')->with('seller_id')->andReturn(12345);
    $requestMock->shouldReceive('input')->with('per_page')->andReturn(15);

    $input = ListItemsInputDTO::fromRequest($requestMock);

    $result = $useCase->execute($input);

    expect($result->totalItems)->toBe(1)
        ->and($result->currentPage)->toBe(1)
        ->and($result->perPage)->toBe(15)
        ->and($result->totalPages)->toBe(1)
        ->and(count($result->items))->toBe(1);
});

it('throws InvalidPaginationException when page is less than 1', function () {
    $repository = Mockery::mock(ItemRepositoryInterface::class);
    $useCase = new ListSavedItemsUseCase($repository);

    $requestMock = Mockery::mock(ListItemsRequest::class);
    $requestMock->shouldReceive('input')->with('page')->andReturn(0);
    $requestMock->shouldReceive('input')->with('seller_id')->andReturn(12345);
    $requestMock->shouldReceive('input')->with('per_page')->andReturn(15);

    $input = ListItemsInputDTO::fromRequest($requestMock);

    expect(static fn () => $useCase->execute($input))
        ->toThrow(InvalidPaginationException::class);
});

it('throws InvalidPaginationException when perPage exceeds 100', function () {
    $repository = Mockery::mock(ItemRepositoryInterface::class);
    $useCase = new ListSavedItemsUseCase($repository);

    $requestMock = Mockery::mock(ListItemsRequest::class);
    $requestMock->shouldReceive('input')->with('page')->andReturn(1);
    $requestMock->shouldReceive('input')->with('seller_id')->andReturn(12345);
    $requestMock->shouldReceive('input')->with('per_page')->andReturn(101);

    $input = ListItemsInputDTO::fromRequest($requestMock);

    expect(static fn () => $useCase->execute($input))
        ->toThrow(InvalidPaginationException::class);
});

it('throws InvalidPaginationException when perPage is less than 1', function () {
    $repository = Mockery::mock(ItemRepositoryInterface::class);
    $useCase = new ListSavedItemsUseCase($repository);

    $requestMock = Mockery::mock(ListItemsRequest::class);
    $requestMock->shouldReceive('input')->with('page')->andReturn(1);
    $requestMock->shouldReceive('input')->with('seller_id')->andReturn(12345);
    $requestMock->shouldReceive('input')->with('per_page')->andReturn(0);

    $input = ListItemsInputDTO::fromRequest($requestMock);

    expect(static fn () => $useCase->execute($input))
        ->toThrow(InvalidPaginationException::class);
});

it('returns empty list when no items exist', function () {
    $collection = new PaginatedItem(
        items: [],
        totalItems: 0,
        currentPage: 1,
        perPage: 15
    );

    $repository = Mockery::mock(ItemRepositoryInterface::class);
    $repository->shouldReceive('findPaginatedBySeller')
        ->andReturn($collection);

    $useCase = new ListSavedItemsUseCase($repository);

    $requestMock = Mockery::mock(ListItemsRequest::class);
    $requestMock->shouldReceive('input')->with('page')->andReturn(1);
    $requestMock->shouldReceive('input')->with('seller_id')->andReturn(12345);
    $requestMock->shouldReceive('input')->with('per_page')->andReturn(15);

    $input = ListItemsInputDTO::fromRequest($requestMock);

    $result = $useCase->execute($input);

    expect($result->totalItems)->toBe(0)
        ->and(count($result->items))->toBe(0)
        ->and($result->totalPages)->toBe(0);
});

it('maps domain entity to response DTO correctly', function () {
    $item = createItem(1, 'MLB456', '999999999');
    $collection = new PaginatedItem(
        items: [$item],
        totalItems: 1,
        currentPage: 1,
        perPage: 15
    );

    $repository = Mockery::mock(ItemRepositoryInterface::class);
    $repository->shouldReceive('findPaginatedBySeller')
        ->andReturn($collection);

    $useCase = new ListSavedItemsUseCase($repository);

    $requestMock = Mockery::mock(ListItemsRequest::class);
    $requestMock->shouldReceive('input')->with('page')->andReturn(1);
    $requestMock->shouldReceive('input')->with('seller_id')->andReturn(12345);
    $requestMock->shouldReceive('input')->with('per_page')->andReturn(15);

    $input = ListItemsInputDTO::fromRequest($requestMock);

    $result = $useCase->execute($input);

    expect($result->items[0]->id)->toBe(1)
        ->and($result->items[0]->meliId)->toBe('MLB456')
        ->and($result->items[0]->sellerId)->toBe('999999999')
        ->and($result->items[0]->title)->toBe('Sample Product')
        ->and($result->items[0]->status)->toBe('active');
});
