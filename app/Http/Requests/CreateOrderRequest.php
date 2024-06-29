<?php

namespace App\Http\Requests;

use App\Rules\SufficientIngredientStock;
use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) {
                    $productId = $this->input(str_replace('.quantity', '.product_id', $attribute));
                    $quantity = $value;

                    $rule = new SufficientIngredientStock($productId, $quantity);
                    $rule->validate($attribute, $value, $fail);
                }
            ],
        ];
    }
}
