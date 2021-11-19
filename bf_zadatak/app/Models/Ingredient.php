<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Astrotomic\Translatable\Translatable;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContracts;

use App\Models\Meal;

class Ingredient extends Model
{
    use HasFactory, Translatable;

    public $translatedAttribues = ["title", "slug"];

    public function meals(){//many-to-many for table "meals"
        return $this->belongsToMany(Meal::class);
    }
}
