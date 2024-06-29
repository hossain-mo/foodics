<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Services\ProductIngredientService;
use App\Models\{
    Ingredient,
    IngredientHistory,
    Product,
    ProductIngredient
};

class DatabaseSeeder extends Seeder
{
    
    public function run(): void
    {
        // Create ingredients
        $beef = Ingredient::create(['name' => 'Beef', 'stock' => 20000, 'remaining' => 20000]);
        $cheese = Ingredient::create(['name' => 'Cheese', 'stock' => 5000, 'remaining' => 5000]);
        $onion = Ingredient::create(['name' => 'Onion', 'stock' => 1000, 'remaining' => 1000]);
        
        // Create product
        $burger = Product::create(['name' => 'Burger']);

        // Associate ingredients with the product
        ProductIngredient::create(['product_id' => $burger->id, 'ingredient_id' => $beef->id, 'quantity' => 150]);
        ProductIngredient::create(['product_id' => $burger->id, 'ingredient_id' => $cheese->id, 'quantity' => 30]);
        ProductIngredient::create(['product_id' => $burger->id, 'ingredient_id' => $onion->id, 'quantity' => 20]);

        // Cache all product ingredients in Redis
        $productIngredientCacheService = new ProductIngredientService();
        $productIngredientCacheService->cacheProductIngredients($burger->id);
    }
}
