<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductIngredient extends Model
{
    use HasFactory;
    protected $fillable = ['product_id', 'ingredient_id', 'quantity'];

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}
