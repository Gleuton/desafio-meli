<?php

namespace App\Console\Commands;

use App\Core\Application\UseCases\FetchSellerAdsUseCase;
use App\Core\Infrastructure\Http\Clients\MeliAuthClient;
use App\Core\Infrastructure\Persistence\ItemRepositoryInterface;
use Illuminate\Console\Command;
use Throwable;

class FetchMeliAdsCommand extends Command
{
    protected $signature = 'meli:fetch-ads 
                            {--seller-id= : The seller ID to fetch ads from (optional, uses config if not provided)}
                            {--limit=30 : Maximum number of ads to fetch}';

    protected $description = 'Fetch advertisements from Mercado Livre and queue them for processing';

    public function handle(
        FetchSellerAdsUseCase $useCase,
        ItemRepositoryInterface $repository,
        MeliAuthClient $authClient
    ): int {
        $sellerId = $this->option('seller-id') ?? config('services.meli.seller_id');
        $limit = (int) $this->option('limit');

        if (empty($sellerId)) {
            $this->error('Seller ID is required. Provide --seller-id or configure services.meli.seller_id');

            return self::FAILURE;
        }

        return $this->executeWithRetry($useCase, $repository, $authClient, $sellerId, $limit);
    }

    private function executeWithRetry(
        FetchSellerAdsUseCase $useCase,
        ItemRepositoryInterface $repository,
        MeliAuthClient $authClient,
        string $sellerId,
        int $limit
    ): int {
        $this->info('Checking database for existing ads...');
        $currentCount = $repository->count();
        $this->info("Current ads in database: {$currentCount}");

        if ($currentCount >= 30) {
            $this->info("Database already has {$currentCount} ads (>= 30).");
            $this->info('Proceeding to update existing ads.');
        }

        $this->info("Fetching up to {$limit} ads from seller: {$sellerId}");

        try {
            $this->info('Verifying access token with Meli Auth...');
            $auth = $authClient->getToken();

            if (($auth['inactive_token'] ?? 1) !== 0) {
                $this->warn('Invalid or inactive token received from Meli Auth service.');
                $this->warn('Reason: Token is inactive or not available');
                $this->newLine();
                $choice = $this->choice('Do you want to retry?', ['y' => 'Yes', 'n' => 'No'], 'Y');

                if ($choice === 'y') {
                    $this->newLine();

                    return $this->executeWithRetry($useCase, $repository, $authClient, $sellerId, $limit);
                }

                return self::SUCCESS;
            }

            $this->info('Valid token received');

            $useCase->execute($sellerId, $auth['access_token'], $limit);
            $this->info("Successfully dispatched jobs to fetch {$limit} ads.");
            $this->info('Jobs will be processed by queue workers.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Failed to fetch ads: {$e->getMessage()}");
            $this->line("Stack trace: {$e->getTraceAsString()}");

            return self::FAILURE;
        }
    }
}
