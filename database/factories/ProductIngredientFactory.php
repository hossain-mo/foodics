<?php

namespace Database\Factories;

use App\Models\Ingredient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Product;
use App\Models\ProductIngredient;

class ProductIngredientFactory extends Factory
{
    protected $model = ProductIngredient::class;

    public function definition()
    {
        return [
            'product_id' => function () {
                return Product::factory()->create()->id;
            },
            'ingredient_id' => function () {
                return Ingredient::factory()->create()->id;
            },
            'quantity' => $this->faker->numberBetween(10, 200),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}