<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    protected $model = Item::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meli_id' => 'MLB'.fake()->unique()->numerify('##########'),
            'title' => fake()->sentence(4),
            'category_id' => 'MLB'.fake()->numerify('####'),
            'price' => fake()->randomFloat(2, 10, 10000),
            'currency_id' => 'BRL',
            'condition' => fake()->randomElement(['new', 'used']),
            'listing_type_id' => fake()->randomElement(['gold_special', 'gold_pro', 'free']),
            'permalink' => fake()->url(),
            'thumbnail' => fake()->imageUrl(640, 480, 'products'),
            'seller_id' => fake()->numerify('########'),
            'status' => 'processed',
            'raw_payload' => [
                'id' => 'MLB'.fake()->numerify('##########'),
                'title' => fake()->sentence(4),
            ],
            'processed_at' => now(),
            'failed_reason' => null,
        ];
    }

    /**
     * Indicate that the item is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'processed_at' => null,
            'raw_payload' => null,
        ]);
    }

    /**
     * Indicate that the item has failed.
     */
    public function failed(?string $reason = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'failed_reason' => $reason ?? 'Test failure reason',
        ]);
    }
}
