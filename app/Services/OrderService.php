<?php
namespace App\Services;

use App\Models\{
    Product,
    ProductIngredient,
    Order
};
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class OrderService
{
    private $ingredientService;
    public function __construct(IngredientService $ingredientService)
    {
        $this->ingredientService = $ingredientService;
    }

    public function create(array $payload)
    {
        return DB::transaction(function () use ($payload) {
            $order = new Order();
            $order->save();

            foreach ($payload['products'] as $productData) {
                $order->products()->attach($productData['product_id'], ['quantity' => $productData['quantity']]);

                $this->ingredientService->updateIngredientStock($productData['product_id'], $productData['quantity']);
            }

            return $order;
        });
    }
}
