<?php

use App\Core\Infrastructure\Http\Clients\MeliItemsClient;
use App\Core\Infrastructure\Http\Contracts\HttpClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

uses(TestCase::class);

test('getItem builds url correctly and returns response data on success', function () {
    $baseUrl = 'https://api.test/';
    $itemId = 'MLB123456789';
    $accessToken = 'token-abc';

    $expectedUrl = 'https://api.test/mercadolibre/items/MLB123456789';
    $expectedOptions = [
        'headers' => [
            'Authorization' => 'Bearer token-abc',
            'Accept' => 'application/json',
        ],
    ];

    $payload = [
        'id' => 'MLB123456789',
        'title' => 'Product Title',
        'price' => 100.50,
        'currency_id' => 'BRL',
    ];

    $httpClient = $this->createMock(HttpClientInterface::class);
    $httpClient->expects($this->once())
        ->method('get')
        ->with($expectedUrl, $expectedOptions)
        ->willReturn($payload);

    $client = new MeliItemsClient($httpClient, $baseUrl);

    expect($client->getItem($itemId, $accessToken))->toMatchArray($payload);
});

test('getItem urlencodes item id', function () {
    $baseUrl = 'https://api.test';
    $itemId = 'MLB 123/456';
    $accessToken = 'token-abc';

    $expectedUrl = 'https://api.test/mercadolibre/items/MLB+123%2F456';

    $payload = [
        'id' => 'MLB 123/456',
        'title' => 'Product Title',
    ];

    $httpClient = $this->createMock(HttpClientInterface::class);
    $httpClient->expects($this->once())
        ->method('get')
        ->with($expectedUrl, $this->anything())
        ->willReturn($payload);

    $client = new MeliItemsClient($httpClient, $baseUrl);

    expect($client->getItem($itemId, $accessToken))->toMatchArray($payload);
});

test('getItem includes authorization header with access token', function () {
    $baseUrl = 'https://api.test';
    $itemId = 'MLB123456789';
    $accessToken = 'my-special-token-xyz';

    $expectedOptions = [
        'headers' => [
            'Authorization' => 'Bearer my-special-token-xyz',
            'Accept' => 'application/json',
        ],
    ];

    $httpClient = $this->createMock(HttpClientInterface::class);
    $httpClient->expects($this->once())
        ->method('get')
        ->with($this->anything(), $expectedOptions)
        ->willReturn(['id' => $itemId]);

    $client = new MeliItemsClient($httpClient, $baseUrl);

    $client->getItem($itemId, $accessToken);
});

test('getItem trims trailing slash from base url', function () {
    $baseUrl = 'https://api.test///';
    $itemId = 'MLB123';
    $accessToken = 'token-abc';

    $expectedUrl = 'https://api.test/mercadolibre/items/MLB123';

    $httpClient = $this->createMock(HttpClientInterface::class);
    $httpClient->expects($this->once())
        ->method('get')
        ->with($expectedUrl, $this->anything())
        ->willReturn(['id' => $itemId]);

    $client = new MeliItemsClient($httpClient, $baseUrl);

    $client->getItem($itemId, $accessToken);
});

test('getItem bubbles up guzzle exceptions', function () {
    $baseUrl = 'https://api.test';
    $itemId = 'MLB123456789';
    $accessToken = 'token-abc';

    $httpClient = $this->createMock(HttpClientInterface::class);
    $httpClient->expects($this->once())
        ->method('get')
        ->willThrowException(new ConnectException('boom', new Request('GET', 'https://api.test')));

    $client = new MeliItemsClient($httpClient, $baseUrl);

    $client->getItem($itemId, $accessToken);
})->throws(ConnectException::class);

test('getItem bubbles up client exceptions', function () {
    $baseUrl = 'https://api.test';
    $itemId = 'MLB123456789';
    $accessToken = 'token-abc';

    $request = new Request('GET', 'https://api.test/mercadolibre/items/MLB123456789');
    $response = new Response(404, [], '{"message": "Item not found"}');
    $exception = new ClientException('Not Found', $request, $response);

    $httpClient = $this->createMock(HttpClientInterface::class);
    $httpClient->expects($this->once())
        ->method('get')
        ->willThrowException($exception);

    $client = new MeliItemsClient($httpClient, $baseUrl);

    $client->getItem($itemId, $accessToken);
})->throws(ClientException::class);
