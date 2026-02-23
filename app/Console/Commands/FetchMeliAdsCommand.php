<?php

namespace App\Console\Commands;

use App\Core\Application\UseCases\FetchSellerAdsUseCase;
use App\Core\Infrastructure\Persistence\ItemRepositoryInterface;
use Illuminate\Console\Command;
use Throwable;

class FetchMeliAdsCommand extends Command
{
    protected $signature = 'meli:fetch-ads 
                            {--seller-id= : The seller ID to fetch ads from (optional, uses config if not provided)}
                            {--limit=30 : Maximum number of ads to fetch}
                            {--force : Force fetching even if 30 ads already exist}';

    protected $description = 'Fetch advertisements from Mercado Livre and queue them for processing';

    public function handle(
        FetchSellerAdsUseCase $useCase,
        ItemRepositoryInterface $repository
    ): int {
        $sellerId = $this->option('seller-id') ?? config('services.meli.seller_id');
        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        if (empty($sellerId)) {
            $this->error('Seller ID is required. Provide --seller-id or configure services.meli.seller_id');

            return self::FAILURE;
        }

        $this->info('Checking database for existing ads...');
        $currentCount = $repository->count();
        $this->info("Current ads in database: {$currentCount}");

        if ($currentCount >= 30 && ! $force) {
            $this->warn("Database already has {$currentCount} ads (>= 30).");
            $this->info('Use --force to fetch and update existing ads.');

            return self::SUCCESS;
        }

        if ($currentCount >= 30 && $force) {
            $this->info('Force mode enabled. Will update existing ads.');
        }

        $this->info("Fetching up to {$limit} ads from seller: {$sellerId}");

        try {
            $useCase->execute($sellerId, $limit);
            $this->info("Successfully dispatched jobs to fetch {$limit} ads.");
            $this->info('Jobs will be processed by queue workers.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Failed to fetch ads: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
