<?php

namespace App\Providers;

use App\Core\Infrastructure\Http\Clients\MeliSearchClient;
use App\Core\Infrastructure\Http\Contracts\HttpClientInterface;
use App\Core\Infrastructure\Http\GuzzleHttpClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(HttpClientInterface::class, GuzzleHttpClient::class);
        $this->app->bind(MeliSearchClient::class, function ($app) {
            return new MeliSearchClient(
                $app->make(HttpClientInterface::class),
                config('services.meli.base_url')
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
