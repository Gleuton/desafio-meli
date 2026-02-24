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
            'status' => 'processed',
            'failed_reason' => null,
            'processed_at' => now(),
            'created' => fake()->dateTimeBetween('-2 weeks', '-1 week'),
            'updated' => fake()->dateTimeBetween('-6 days', 'now'),
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
            'failed_reason' => null,
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
            'processed_at' => null,
        ]);
    }
}
