<?php

declare(strict_types=1);

use App\Core\Infrastructure\Http\Clients\MeliAuthClient;
use App\Core\Infrastructure\Http\Clients\MeliItemsClient;
use App\Core\Infrastructure\Http\Clients\MeliSearchClient;

it('can fetch item details using bearer token', function () {
    $authClient = app(MeliAuthClient::class);
    $searchClient = app(MeliSearchClient::class);
    $itemsClient = app(MeliItemsClient::class);

    $auth = $authClient->getToken();

    if (($auth['inactive_token'] ?? 1) !== 0) {
        $this->markTestSkipped('Inactive token returned by mock');
    }

    $search = $searchClient->searchBySeller(
        sellerId: '252254392',
        accessToken: $auth['access_token'],
        limit: 1,
    );

    if (empty($search['results'])) {
        $this->markTestSkipped('No items returned from search');
    }

    $itemId = $search['results'][0];

    $item = $itemsClient->getItem($itemId, $auth['access_token']);

    expect($item)->not()->toBeNull()
        ->and($item)->toBeArray()
        ->and($item)->toHaveKey('id')
        ->and($item['id'])->toBe($itemId);
});
