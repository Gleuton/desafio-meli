<?php

namespace App\Providers;

use App\Core\Application\Contracts\LoggerInterface;
use App\Core\Application\Contracts\QueueDispatcherInterface;
use App\Core\Infrastructure\Http\Clients\MeliAuthClient;
use App\Core\Infrastructure\Http\Clients\MeliItemsClient;
use App\Core\Infrastructure\Http\Clients\MeliSearchClient;
use App\Core\Infrastructure\Http\Contracts\HttpClientInterface;
use App\Core\Infrastructure\Http\GuzzleHttpClient;
use App\Core\Infrastructure\Logging\LaravelLogger;
use App\Core\Infrastructure\Persistence\EloquentItemRepository;
use App\Core\Infrastructure\Persistence\ItemRepositoryInterface;
use App\Core\Infrastructure\Queue\LaravelQueueDispatcher;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(HttpClientInterface::class, GuzzleHttpClient::class);

        $this->app->bind(
            LoggerInterface::class,
            LaravelLogger::class
        );

        $this->app->bind(MeliAuthClient::class, function ($app) {
            return new MeliAuthClient(
                $app->make(HttpClientInterface::class),
                config('services.meli.base_url'),
                config('services.meli.seller_id')
            );
        });

        $this->app->bind(MeliSearchClient::class, function ($app) {
            return new MeliSearchClient(
                $app->make(HttpClientInterface::class),
                config('services.meli.base_url')
            );
        });

        $this->app->bind(MeliItemsClient::class, function ($app) {
            return new MeliItemsClient(
                $app->make(HttpClientInterface::class),
                config('services.meli.base_url')
            );
        });

        $this->app->bind(
            QueueDispatcherInterface::class,
            LaravelQueueDispatcher::class
        );

        $this->app->bind(
            ItemRepositoryInterface::class,
            EloquentItemRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
