<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'sku' => Str::upper(Str::random(10)),
            'description' => fake()->sentence(),
            'price_cents' => 1000,
            'stock' => 10,
            'is_active' => true,
        ];
    }

    public function stock(int $stock): static
    {
        return $this->state(['stock' => $stock]);
    }
}
