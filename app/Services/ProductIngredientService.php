<?php
namespace App\Services;

use App\Models\Product;
use App\Models\ProductIngredient;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ProductIngredientService
{
    public function getProductIngredients($product_id)
    {
        $productIngredients = ProductIngredient::where('product_id', $product_id)
            ->get()
            ->map(function ($productIngredient) {
                return [
                    'id' => $productIngredient->ingredient_id,
                    'quantity' => $productIngredient->quantity,
                ];
            })
            ->toArray();
        return $productIngredients;
    }

    public function cacheProductIngredients($product_id)
    {
        $productIngredients = $this->getProductIngredients($product_id);
        $redisKey = "product_{$product_id}_ingredients";
        try{
            Redis::set($redisKey, json_encode($productIngredients));
        }
        catch(Exception $e)
        {
            Log::error("Error In Connecting to Redis". $e->getMessage());
        }
    }
}
