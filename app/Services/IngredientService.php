<?php
namespace App\Services;

use App\Models\Ingredient;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\LowStockAlert;
use App\Jobs\UpdateIngredientStockJob;
use Exception;

class IngredientService
{
    private $productIngredientService;
    public function __construct(ProductIngredientService $productIngredientService)
    {
        $this->productIngredientService = $productIngredientService;
    }

    public function updateIngredientStock(int $productId, int $quantity)
    {
        dispatch(new UpdateIngredientStockJob($productId, $quantity));
    }

    public function processStockUpdate(int $productId, int $quantity)
    {
        $redisKey = "product_{$productId}_ingredients";
        
        $productIngredients = json_decode(Redis::get($redisKey), true);
    
        $productIngredients = $productIngredients ?? $this->productIngredientService->getProductIngredients($productId);

        foreach ($productIngredients as $ingredientData) {
            $ingredient = Ingredient::find($ingredientData['id']);
            $ingredient->remaining -= $ingredientData['quantity'] * $quantity;
            $ingredient->save();

            $this->checkAndSendLowStockAlert($ingredient);
        }
    }

    protected function checkAndSendLowStockAlert(Ingredient $ingredient)
    {
        if ($ingredient->remaining < $ingredient->stock * 0.5 && !$ingredient->low_stock_alert_sent) {
            Mail::to(env('MERCHANT_EMAIL'))->send(new LowStockAlert($ingredient));
            $ingredient->low_stock_alert_sent = true;
            $ingredient->save();
        }
    }
}
