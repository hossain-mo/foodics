<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IngredientHistory extends Model
{
    protected $fillable = ['ingredient_id', 'stock'];
}
