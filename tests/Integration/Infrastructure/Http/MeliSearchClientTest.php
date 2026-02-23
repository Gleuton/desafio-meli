<?php

declare(strict_types=1);

use App\Core\Infrastructure\Http\Clients\MeliAuthClient;
use App\Core\Infrastructure\Http\Clients\MeliSearchClient;

it('can search items by seller with bearer token', function () {
    $authClient = app(MeliAuthClient::class);
    $searchClient = app(MeliSearchClient::class);

    $auth = $authClient->getToken();

    if (($auth['inactive_token'] ?? 1) !== 0) {
        $this->markTestSkipped('Inactive token returned by mock');
    }

    $data = $searchClient->searchBySeller(
        sellerId: '252254392',
        accessToken: $auth['access_token']
    );

    expect($data)->not()->toBeNull()
        ->and($data)->toHaveKey('results');
});
