<?php

namespace App\Rules;

use App\Models\ProductIngredient;
use Illuminate\Contracts\Validation\ValidationRule;

class SufficientIngredientStock implements ValidationRule
{
    protected $productId;
    protected $quantity;
    protected $insufficientIngredients = [];

    public function __construct($productId, $quantity)
    {
        $this->productId = $productId;
        $this->quantity = $quantity;
    }

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $productIngredients = ProductIngredient::where('product_id', $this->productId)->get();

        foreach ($productIngredients as $productIngredient) {
            $ingredient = $productIngredient->ingredient;
            $requiredQuantity = $productIngredient->quantity * $this->quantity;
            if ($ingredient->remaining < $requiredQuantity) {
                $this->insufficientIngredients[] =  $ingredient->id;
            }
        }
        
        if (!empty($this->insufficientIngredients)) {
            $fail('Insufficient stock for some ingredients for the product: ' . $this->productId);
        }
    }
}
