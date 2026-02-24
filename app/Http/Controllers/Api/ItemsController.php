<?php

namespace App\Http\Controllers\Api;

use App\Core\Application\DTOs\Input\ListItemsInputDTO;
use App\Core\Application\UseCases\ListSavedItemsUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListItemsRequest;
use Illuminate\Http\JsonResponse;

class ItemsController extends Controller
{
    public function __construct(
        private readonly ListSavedItemsUseCase $useCase,
    ) {}

    public function items(ListItemsRequest $request): JsonResponse
    {
        $input = ListItemsInputDTO::fromRequest($request);

        $result = $this->useCase->execute($input);

        return response()->json([
            'status' => 'success',
            'data' => array_map(static fn ($item) => $item->toArray(), $result->items),
            'pagination' => [
                'current_page' => $result->currentPage,
                'per_page' => $result->perPage,
                'total' => $result->totalItems,
                'last_page' => $result->totalPages,
            ],
        ]);
    }
}
