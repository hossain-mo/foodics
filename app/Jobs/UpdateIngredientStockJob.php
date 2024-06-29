<?php

namespace App\Jobs;

use App\Models\Ingredient;
use App\Services\IngredientService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateIngredientStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $productId;
    protected $quantity;

    public function __construct(int $productId, int $quantity)
    {
        $this->productId = $productId;
        $this->quantity = $quantity;
    }

    public function handle(IngredientService $ingredientService)
    {
        $ingredientService->processStockUpdate($this->productId, $this->quantity);
    }
}
