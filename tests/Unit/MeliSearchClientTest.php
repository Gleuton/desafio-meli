<?php

use App\Core\Infrastructure\Http\Clients\MeliSearchClient;
use App\Core\Infrastructure\Http\Contracts\HttpClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use Tests\TestCase;

uses(TestCase::class);

test('searchBySeller builds url with default pagination and headers', function () {
    $baseUrl = 'https://api.test/';
    $sellerId = 'seller-1';
    $accessToken = 'token-abc';

    $expectedUrl = 'https://api.test/mercadolibre/sites/MLB/search?seller_id=seller-1&offset=0&limit=30';
    $expectedOptions = [
        'headers' => [
            'Authorization' => 'Bearer '.$accessToken,
            'Accept' => 'application/json',
        ],
    ];

    $payload = ['results' => []];

    $httpClient = $this->createMock(HttpClientInterface::class);
    $httpClient->expects($this->once())
        ->method('get')
        ->with($expectedUrl, $expectedOptions)
        ->willReturn($payload);

    $client = new MeliSearchClient($httpClient, $baseUrl);

    expect($client->searchBySeller($sellerId, $accessToken))->toMatchArray($payload);
});

test('searchBySeller uses custom pagination and urlencodes seller id', function () {
    $baseUrl = 'https://api.test';
    $sellerId = 'seller id/1';
    $accessToken = 'token-abc';

    $expectedUrl = 'https://api.test/mercadolibre/sites/MLB/search?seller_id=seller+id%2F1&offset=20&limit=10';

    $httpClient = $this->createMock(HttpClientInterface::class);
    $httpClient->expects($this->once())
        ->method('get')
        ->with($expectedUrl, $this->anything())
        ->willReturn(['ok' => true]);

    $client = new MeliSearchClient($httpClient, $baseUrl);

    expect($client->searchBySeller($sellerId, $accessToken, 10, 20))->toMatchArray(['ok' => true]);
});

test('searchBySeller bubbles up guzzle exceptions', function () {
    $baseUrl = 'https://api.test';
    $sellerId = 'seller-1';
    $accessToken = 'token-abc';

    $httpClient = $this->createMock(HttpClientInterface::class);
    $httpClient->expects($this->once())
        ->method('get')
        ->willThrowException(new ConnectException('boom', new Request('GET', 'https://api.test')));

    $client = new MeliSearchClient($httpClient, $baseUrl);

    $client->searchBySeller($sellerId, $accessToken);
})->throws(ConnectException::class);
