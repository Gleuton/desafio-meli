<?php

namespace App\Core\Application\DTOs\Input;

use App\Http\Requests\ListItemsRequest;

class ListItemsInputDTO
{
    private function __construct(
        public readonly ?string $sellerId,
        public readonly int $page,
        public readonly int $perPage,
    ) {}

    public static function fromRequest(ListItemsRequest $request): self
    {
        return new self(
            $request->input('seller_id'),
            $request->input('page') ?? 1,
            $request->input('per_page') ?? 15,
        );
    }
}
