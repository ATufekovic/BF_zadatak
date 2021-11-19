<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContracts;

class Ingredient extends Model
{
    use HasFactory, Translatable;

    public $translatedAttribues = ["title", "slug"];
}
