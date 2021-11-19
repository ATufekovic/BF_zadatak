<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Astrotomic\Translatable\Translatable;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContracts;

use App\Models\Meal;

class Category extends Model
{
    use HasFactory;

    public $translatedAttribues = ["title", "slug"];

    public function meals(){
        return $this->hasMany(Meal::class);
    }
}
