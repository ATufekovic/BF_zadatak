<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Astrotomic\Translatable\Translatable;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContracts;

use App\Models\Meal;

class Category extends Model
{
    use HasFactory, Translatable;
    public $timestamps = false;

    public $translatedAttributes = ["title"];

    public function meals(){//one-to-many for table "meals"
        return $this->hasMany(Meal::class);
    }
}
